import math
from datetime import datetime
from src.core.logger import logger
from src.handlers.base_handler import BaseHandler
from src.core.ai_engine import AiEngine
from src.core.core_client import CoreClient
from src.utils.image_utils import get_thumbnail, get_thumbnail_size
from src.core.distributed_cache import DistributedCache
from typing import List, Optional, Final
from torch import Tensor
from concurrent.futures import ThreadPoolExecutor
from collections import Counter
import time
import hashlib

NEGATIVE_QUERY: Final[str] = (
    "Macro photography, close-up, single object detail, stairs, interiors, overcast sky, "
    "museum exhibits, interior furniture, blurry background, gesturing people, flags, "
    "insects, textured surfaces, night darkness, running children."
)

CONTENT_QUERY_CACHE_KEY_FORMAT = (
    "HighlightsSelectingTriggeredHandler:ContentQuery:{prompt_template}:{entity_id}"
)
CONTENT_QUERY_CACHE_TTL: Final[int] = 365 * 86400

SELECTED_HIGHLIGHTS_CACHE_KEY_FORMAT = (
    "HighlightsSelectingTriggeredHandler:SelectedHighlights:{fingerprint}"
)
SELECTED_HIGHLIGHTS_CACHE_TTL: Final[int] = 86400


class HighlightsSelectingTriggeredHandler(BaseHandler):
    def __init__(
        self,
        ai_engine: AiEngine,
        core_client: CoreClient,
        distributed_cache: DistributedCache,
        max_threads: int,
        iso_coeff: float,
    ) -> None:
        self.ai_engine = ai_engine
        self.core_client = core_client
        self.distributed_cache = distributed_cache
        self.max_workers = 2 * max_threads
        self.iso_coeff = iso_coeff

    def handle(self, args: dict) -> None:
        entity_id = args.get("entityId")
        highlights_count = int(args.get("highlightsCount"))
        highlights_removal_allowed = args.get("highlightsRemovalAllowed", False)

        match args.get("highlightType"):
            case "place":
                self._handle_place(
                    entity_id, highlights_count, highlights_removal_allowed
                )
                return
            case "trip":
                self._handle_trip(
                    entity_id, highlights_count, highlights_removal_allowed
                )
                return
            case "category":
                self._handle_category(
                    entity_id, highlights_count, highlights_removal_allowed
                )
                return
            case "year":
                self._handle_year(
                    int(entity_id), highlights_count, highlights_removal_allowed
                )
                return
            case _:
                raise ValueError(
                    f"Unknown highlight type '{args.get('highlightType')}' encountered."
                )

    def _handle_year(
        self, year_id: int, highlights_count: int, highlights_removal_allowed: bool
    ) -> None:
        year = self.core_client.get_year(year_id)
        year_places = self.core_client.get_places(
            year=year_id, max_end=int(time.time()), include="highlights"
        )
        content_query = self._get_or_create_content_prompt(
            prompt_template="yearHighlightsSelecting",
            entity_id=str(year_id),
            context={
                "model": self.ai_engine.model_name,
                "places": ", ".join(
                    p["name"]
                    for p in sorted(
                        year_places, key=lambda x: x.get("score", 0), reverse=True
                    )
                ),
            },
        )
        logger.debug(f"Using the content query '{content_query}'...")

        selected_photo_ids = self._handle_entity(
            entity_name=str(year_id),
            highlights_count=highlights_count,
            places=year_places,
            existing_highlights=year.get("highlights", []),
            photos=self._fetch_photos_for_category_or_year(
                year_places, year.get("highlights", []), highlights_count
            ),
            content_query=content_query,
            main_highlight_photo_id=year.get("mainHighlight", {})
            .get("photo", {})
            .get("id"),
            highlights_removal_allowed=highlights_removal_allowed,
        )

        for h_id in self._get_highlight_ids_to_remove(
            year.get("highlights", []), selected_photo_ids
        ):
            self.core_client.remove_year_highlight(year_id, h_id)

        for p_id in self._get_photo_ids_to_create(
            year.get("highlights", []), selected_photo_ids
        ):
            self.core_client.create_year_highlight(year_id, p_id)

    def _handle_category(
        self, category_id: str, highlights_count: int, highlights_removal_allowed: bool
    ) -> None:
        category = self.core_client.get_category(category_id)
        category_places = self.core_client.get_places(
            category_id=category_id, max_end=int(time.time()), include="highlights"
        )
        content_query = self._get_or_create_content_prompt(
            prompt_template="categoryHighlightsSelecting",
            entity_id=category_id,
            context={
                "model": self.ai_engine.model_name,
                "name": category.get("name"),
                "places": ", ".join(
                    p["name"]
                    for p in sorted(
                        category_places, key=lambda x: x.get("score", 0), reverse=True
                    )
                ),
            },
        )
        logger.debug(f"Using the content query '{content_query}'...")

        selected_photo_ids = self._handle_entity(
            entity_name=category.get("name"),
            highlights_count=highlights_count,
            places=category_places,
            existing_highlights=category.get("highlights", []),
            photos=self._fetch_photos_for_category_or_year(
                category_places, category.get("highlights", []), highlights_count
            ),
            content_query=content_query,
            main_highlight_photo_id=category.get("mainHighlight", {})
            .get("photo", {})
            .get("id"),
            highlights_removal_allowed=highlights_removal_allowed,
        )

        for h_id in self._get_highlight_ids_to_remove(
            category.get("highlights", []), selected_photo_ids
        ):
            self.core_client.remove_category_highlight(category_id, h_id)

        for p_id in self._get_photo_ids_to_create(
            category.get("highlights", []), selected_photo_ids
        ):
            self.core_client.create_category_highlight(category_id, p_id)

    def _handle_trip(
        self, trip_id: str, highlights_count: int, highlights_removal_allowed: bool
    ) -> None:
        trip = self.core_client.get_trip(trip_id)
        trip_places = [
            p
            for p in self.core_client.get_places(
                trip_id=trip_id, max_end=int(time.time()), include="dates"
            )
            if not p.get("layover")
        ]
        content_query = self._get_or_create_content_prompt(
            prompt_template="tripHighlightsSelecting",
            entity_id=trip_id,
            context={
                "model": self.ai_engine.model_name,
                "places": ", ".join(
                    p["name"]
                    for p in sorted(
                        trip_places, key=lambda x: x.get("score", 0), reverse=True
                    )
                ),
            },
        )
        logger.debug(f"Using the content query '{content_query}'...")

        selected_photo_ids = self._handle_entity(
            entity_name=f"{trip.get('name')} {trip.get('year')}",
            highlights_count=highlights_count,
            places=trip_places,
            existing_highlights=trip.get("highlights", []),
            photos=self._fetch_photos_for_place_or_trip(
                trip_places, trip.get("highlights", []), highlights_count
            ),
            content_query=content_query,
            main_highlight_photo_id=trip.get("mainHighlight", {})
            .get("photo", {})
            .get("id"),
            highlights_removal_allowed=highlights_removal_allowed,
        )

        for h_id in self._get_highlight_ids_to_remove(
            trip.get("highlights", []), selected_photo_ids
        ):
            self.core_client.remove_trip_highlight(trip_id, h_id)

        for p_id in self._get_photo_ids_to_create(
            trip.get("highlights", []), selected_photo_ids
        ):
            self.core_client.create_trip_highlight(trip_id, p_id)

    def _handle_place(
        self, place_id: str, highlights_count: int, highlights_removal_allowed: bool
    ) -> None:
        place = self.core_client.get_place(place_id)
        content_query = self._get_or_create_content_prompt(
            prompt_template="placeHighlightsSelecting",
            entity_id=place_id,
            context={
                "model": self.ai_engine.model_name,
                "name": place.get("name"),
                "country": place.get("country"),
            },
        )
        logger.debug(f"Using the content query '{content_query}'...")

        selected_photo_ids = self._handle_entity(
            entity_name=place.get("name"),
            highlights_count=highlights_count,
            places=[place],
            existing_highlights=place.get("highlights", []),
            photos=self._fetch_photos_for_place_or_trip(
                [place], place.get("highlights", []), highlights_count
            ),
            content_query=content_query,
            main_highlight_photo_id=place.get("mainHighlight", {})
            .get("photo", {})
            .get("id"),
            highlights_removal_allowed=highlights_removal_allowed,
        )

        for h_id in self._get_highlight_ids_to_remove(
            place.get("highlights", []), selected_photo_ids
        ):
            self.core_client.remove_place_highlight(place_id, h_id)

        for p_id in self._get_photo_ids_to_create(
            place.get("highlights", []), selected_photo_ids
        ):
            self.core_client.create_place_highlight(place_id, p_id)

    def _fetch_photos_for_place_or_trip(
        self, places: List[dict], existing_highlights: List[dict], highlights_count: int
    ) -> List[dict]:
        width, height = get_thumbnail_size()
        return (
            [
                {**p, "url": f"{p['url']}=w{width}-h{height}"}
                for p in self._get_all_photos(places)
            ]
            if highlights_count > len(existing_highlights)
            else [highlight.get("photo") for highlight in existing_highlights]
        )

    def _fetch_photos_for_category_or_year(
        self, places: List[dict], existing_highlights: List[dict], highlights_count: int
    ) -> List[dict]:
        return (
            [h.get("photo") for p in places for h in p.get("highlights", [])]
            if highlights_count > len(existing_highlights)
            else [highlight.get("photo") for highlight in existing_highlights]
        )

    def _handle_entity(
        self,
        entity_name: str,
        highlights_count: int,
        places: List[dict],
        existing_highlights: List[dict],
        photos: List[dict],
        content_query: str,
        main_highlight_photo_id: Optional[str],
        highlights_removal_allowed: bool,
    ) -> Optional[List[str]]:
        delta = highlights_count - len(existing_highlights)
        if (
            (not highlights_removal_allowed and delta <= 0)
            or highlights_count == 0
        ):
            logger.warning(
                f"There are already {len(existing_highlights)} highlights for {entity_name}. No new highlights will be created."
            )
            return None
        
        fingerprint = f"{self.ai_engine.model_name}|{entity_name}|{highlights_count}|{content_query}|{main_highlight_photo_id}" \
            f"{sorted(p['id'] for p in places)}|" \
            f"{sorted(p['id'] for p in photos)}|" \
            f"{sorted(eh['id'] for eh in existing_highlights)}"

        cache_key = SELECTED_HIGHLIGHTS_CACHE_KEY_FORMAT.format(fingerprint=hashlib.md5(fingerprint.encode()).hexdigest())
        cached_highlights = self.distributed_cache.get(cache_key, SELECTED_HIGHLIGHTS_CACHE_TTL)
        if cached_highlights:
            return cached_highlights        

        with ThreadPoolExecutor(max_workers=self.max_workers) as executor:
            preprocessed_photos = list(
                filter(None, executor.map(self._preprocess_photo, photos))
            )

        if not preprocessed_photos:
            logger.warning(
                f"There are no photos for {entity_name}. No new highlights will be created."
            )
            return None

        if delta < 0:
            logger.debug(
                f"Analyzing {len(preprocessed_photos)} photos for {entity_name} (removing {(-1) * delta} highlights)..."
            )
        else:
            logger.debug(
                f"Analyzing {len(preprocessed_photos)} photos for {entity_name} (creating {delta} highlights)..."
            )

        img_embeddings = self.ai_engine.get_image_embedding(
            [p.get("img") for p in preprocessed_photos]
        )
        scores = self._calculate_scores(
            content_query, img_embeddings, preprocessed_photos, places
        )

        photo_data = []
        for i in range(len(preprocessed_photos)):
            photo_data.append(
                {
                    "photo": preprocessed_photos[i],
                    "score": scores[i],
                    "emb": img_embeddings[i],
                }
            )

        cluster_labels = self.ai_engine.cluster_embeddings(img_embeddings)
        cluster_counts = Counter(cluster_labels)

        enriched_data = []
        for i in range(len(preprocessed_photos)):
            label = cluster_labels[i]
            size = cluster_counts[label]
            priority = scores[i] * (size**2)

            enriched_data.append(
                {
                    "idx": i,
                    "label": label,
                    "size": size,
                    "score": scores[i],
                    "priority": priority,
                    "photo": preprocessed_photos[i],
                }
            )

        enriched_data.sort(
            key=lambda d: (
                d["photo"].get("id") != main_highlight_photo_id,
                -d["priority"],
            )
        )

        selected_indices = []
        used_clusters = set()

        for d in enriched_data:
            if len(selected_indices) >= highlights_count:
                break

            if d["label"] not in used_clusters:
                selected_indices.append(d["idx"])
                used_clusters.add(d["label"])
                logger.debug(
                    f"Selecting a highlight with score {scores[d['idx']]} for {entity_name} ({preprocessed_photos[d['idx']].get('url')})..."
                )

        for d in enriched_data:
            if len(selected_indices) >= highlights_count:
                break

            if d["idx"] not in selected_indices:
                selected_indices.append(d["idx"])
                logger.debug(
                    f"Selecting a highlight with score {scores[d['idx']]} for {entity_name} ({preprocessed_photos[d['idx']].get('url')})..."
                )

        result = [preprocessed_photos[i].get("id") for i in selected_indices]
        self.distributed_cache.set(cache_key, result, SELECTED_HIGHLIGHTS_CACHE_TTL)
        return result

    def get_handled_event_names(self) -> List[str]:
        return ["HighlightsSelectingTriggered"]

    @staticmethod
    def _preprocess_photo(p: dict) -> Optional[dict]:
        img = get_thumbnail(p["url"])
        if img is None:
            return None

        return {**p, "img": img}

    def _get_existing_image_embeddings(
        self,
        existing_highlight_photo_ids: List[str],
        img_embeddings: Tensor,
        preprocessed_photos: List[dict],
    ) -> List[Tensor]:
        selected_embeddings = []

        for i, p in enumerate(preprocessed_photos):
            if p.get("id") in existing_highlight_photo_ids:
                selected_embeddings.append(img_embeddings[i])

        return selected_embeddings

    def _calculate_scores(
        self,
        content_query: str,
        img_embeddings: Tensor,
        preprocessed_photos: List[dict],
        places: List[dict],
    ) -> List[Tensor]:
        style_vector = self._get_style_vector()
        content_emb = self.ai_engine.get_text_embedding(content_query)
        negative_emb = self.ai_engine.get_text_embedding(NEGATIVE_QUERY)

        scores = (
            self.ai_engine.calculate_scores(
                content_emb, img_embeddings, style_vector, negative_emb
            )
            .cpu()
            .numpy()
        )

        min_iso_log = math.log2(
            max(min(p.get("iso", 100) for p in preprocessed_photos), 1)
        )
        max_iso_log = math.log2(
            max(max(p.get("iso", 100) for p in preprocessed_photos), 1)
        )
        iso_log_range = max_iso_log - min_iso_log if max_iso_log > min_iso_log else 1

        main_highlight_photo_ids = {
            p.get("mainHighlight", {}).get("photo", {}).get("id")
            for p in places
            if p.get("mainHighlight")
        }

        final_scores = []
        for i, p in enumerate(preprocessed_photos):
            base_quality = float(scores[i])
            iso_bonus = (
                (max_iso_log - math.log2(max(p.get("iso", 100), 1))) / iso_log_range
            ) * self.iso_coeff
            main_highlight_bonus = (
                base_quality if p.get("id") in main_highlight_photo_ids else 0.0
            )
            final_scores.append(
                base_quality + main_highlight_bonus + iso_bonus
            )

        return final_scores

    def _get_style_vector(self) -> Optional[Tensor]:
        reference_photos = []

        all_places = self.core_client.get_places()
        for p in all_places:
            h = p.get("mainHighlight")
            if h is not None:
                reference_photos.append(h.get("photo"))

        return self.ai_engine.extract_style_context(reference_photos)

    def _get_all_photos(self, places: List[dict]) -> List[dict]:
        all_photos = []

        for place in places:
            for date in place.get("dates", []):
                album_id = date.get("album", {}).get("id")
                if album_id:
                    photos = self.core_client.get_place_album_photos(
                        place.get("id"), album_id
                    )
                    all_photos.extend(
                        [{**p, "placeId": place.get("id")} for p in photos]
                    )

        return all_photos

    def _get_photo_ids_to_create(
        self, existing_highlights: List[dict], selected_photo_ids: Optional[List[str]]
    ) -> List[str]:
        if selected_photo_ids is None:
            return []

        existing_photo_ids = {h.get("photo").get("id") for h in existing_highlights}
        return [
            photo_id
            for photo_id in selected_photo_ids
            if photo_id not in existing_photo_ids
        ]

    def _get_highlight_ids_to_remove(
        self, existing_highlights: List[dict], selected_photo_ids: Optional[List[str]]
    ) -> List[str]:
        if selected_photo_ids is None:
            return []

        return [
            highlight.get("id")
            for highlight in existing_highlights
            if highlight.get("photo").get("id") not in selected_photo_ids
        ]

    def _get_or_create_content_prompt(
        self, prompt_template: str, entity_id: str, context: dict
    ) -> str:
        content_query_cache_key = CONTENT_QUERY_CACHE_KEY_FORMAT.format(
            prompt_template=prompt_template, entity_id=entity_id
        )

        content_query = self.distributed_cache.get(
            content_query_cache_key, CONTENT_QUERY_CACHE_TTL
        )

        if content_query is not None and content_query["context"] == context:
            return content_query["query"]

        content_query = {
            "query": self.core_client.create_generative_content(
                prompt_template=prompt_template, context=context
            ),
            "context": context,
        }

        self.distributed_cache.set(
            content_query_cache_key, content_query, CONTENT_QUERY_CACHE_TTL
        )

        return content_query["query"]
