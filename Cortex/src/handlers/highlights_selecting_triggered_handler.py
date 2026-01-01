from datetime import datetime
from src.core.logger import logger
from src.handlers.base_handler import BaseHandler
from src.core.ai_engine import AiEngine
from src.core.core_client import CoreClient
from src.utils.image_utils import get_thumbnail, get_thumbnail_size
from typing import List, Optional, Final
from torch import Tensor
from concurrent.futures import ThreadPoolExecutor

CONTENT_QUERY: Final[str] = (
    "Famous landmarks and iconic monuments, street life, cinematic soft lighting, high quality, sunny weather, "
    "clear blue sky, breathtaking composition, beautiful landscapes."
)

PLACE_CONTENT_QUERY_FORMAT: Final[str] = (
    "Professional exterior travel photography of {place_name} in {country_name}. "
    + CONTENT_QUERY
)

NEGATIVE_QUERY: Final[str] = (
    "Macro photography, close-up, single object detail, stairs, interiors, overcast sky, "
    "museum exhibits, interior furniture, blurry background, gesturing people, "
    "insects, textured surfaces, night darkness, running children."
)


class HighlightsSelectingTriggeredHandler(BaseHandler):
    def __init__(
        self,
        ai_engine: AiEngine,
        core_client: CoreClient,
        max_threads: int,
        age_coeff: float,
        similarity_threshold: float,
    ) -> None:
        self.ai_engine = ai_engine
        self.core_client = core_client
        self.max_workers = 2 * max_threads
        self.age_coeff = age_coeff
        self.similarity_threshold = similarity_threshold

    def handle(self, args: dict) -> None:
        entity_id = args.get("entityId")
        highlights_count = int(args.get("highlightsCount"))

        match args.get("highlightType"):
            case "place":
                self._handle_place(entity_id, highlights_count)
                return
            case "trip":
                self._handle_trip(entity_id, highlights_count)
                return
            case "category":
                # TODO: Implement.
                return
            case "year":
                # TODO: Implement.
                return
            case _:
                raise ValueError(
                    f"Unknown highlight type '{args.get('highlightType')}' encountered."
                )

    def _handle_trip(self, trip_id: str, highlights_count: int) -> None:
        trip = self.core_client.get_trip(trip_id)
        trip_places = self.core_client.get_places(trip_id=trip_id, include="dates")

        selected_photo_ids = self._handle_entity(
            f"{trip.get('name')} {trip.get('year')}",
            highlights_count,
            trip_places,
            trip.get("highlights", []),
            CONTENT_QUERY,
        )

        if selected_photo_ids is not None:
            for selected_photo_id in selected_photo_ids:
                self.core_client.create_trip_highlight(trip_id, selected_photo_id)

    def _handle_place(self, place_id: str, highlights_count: int) -> None:
        place = self.core_client.get_place(place_id)
        content_query = PLACE_CONTENT_QUERY_FORMAT.format(
            place_name=place.get("name"), country_name=place.get("country")
        )

        selected_photo_ids = self._handle_entity(
            place.get("name"),
            highlights_count,
            [place],
            place.get("highlights", []),
            content_query,
        )

        if selected_photo_ids is not None:
            for selected_photo_id in selected_photo_ids:
                self.core_client.create_place_highlight(place_id, selected_photo_id)

    def _handle_entity(
        self,
        entity_name: str,
        highlights_count: int,
        places: List[dict],
        existing_highlights: List[dict],
        content_query: str,
    ) -> Optional[List[str]]:
        try:
            # Preprocess photos in all albums.
            with ThreadPoolExecutor(max_workers=self.max_workers) as executor:
                preprocessed_photos = list(
                    filter(
                        None,
                        executor.map(
                            self._preprocess_photo, self._get_all_photos(places)
                        ),
                    )
                )

            if not preprocessed_photos:
                logger.warning(
                    f"There are no photos for {entity_name}. No new highlights will be created."
                )
                return None

            if len(existing_highlights) >= highlights_count:
                logger.warning(
                    f"There are already {len(existing_highlights)} highlights for {entity_name}. No new highlights will be created."
                )
                return None

            logger.debug(
                f"Analyzing {len(preprocessed_photos)} photos for {entity_name} (selecting {highlights_count - len(existing_highlights)} highlights)..."
            )

            # Compute embeddings for all photos, select embeddings for already existing highlights.
            img_embeddings = self.ai_engine.get_image_embedding(
                [p.get("img") for p in preprocessed_photos]
            )

            existing_highlight_photo_ids = [
                h.get("photo").get("id") for h in existing_highlights
            ]
            selected_embeddings = self._get_existing_image_embeddings(
                existing_highlight_photo_ids, img_embeddings, preprocessed_photos
            )

            candidate_indices = [
                i
                for i, p in enumerate(preprocessed_photos)
                if p.get("id") not in existing_highlight_photo_ids
            ]
            skipped_indices = []

            # Compute score for all photos and prepare a sorted list of highlight candidates.
            scores = self._calculate_scores(
                content_query, img_embeddings, preprocessed_photos
            )
            candidate_indices.sort(key=lambda i: scores[i], reverse=True)

            selected_photo_ids = []
            for candidate_idx in candidate_indices:
                if len(selected_embeddings) >= highlights_count:
                    break

                # Evaluate whether the current highlight candidate is "too similar" to already existing highlights.
                is_too_similar = False
                if selected_embeddings:
                    max_similarity = self.ai_engine.calculate_max_similarity(
                        img_embeddings[candidate_idx], selected_embeddings
                    )

                    if max_similarity > self.similarity_threshold:
                        is_too_similar = True
                        logger.debug(
                            f"The photo with score {scores[candidate_idx]} is too similar ({int(100.0 * max_similarity)}%) to already selected highlights for {entity_name} and will therefore be skipped ({preprocessed_photos[candidate_idx].get('url')})."
                        )

                # If the highlight candidate passed the similarity filter, create the highlight and write down its embeddings for the next similarity filter iteration.
                if not is_too_similar:
                    logger.debug(
                        f"Creating a unique highlight with score {scores[candidate_idx]} for {entity_name} ({preprocessed_photos[candidate_idx].get('url')})..."
                    )

                    selected_photo_ids.append(
                        preprocessed_photos[candidate_idx].get("id")
                    )
                    selected_embeddings.append(img_embeddings[candidate_idx])
                else:
                    skipped_indices.append(candidate_idx)

            # If there are not enough photos for the place to pass the similarity filter, create highlights for the best photos even though they didn't pass the filter.
            for skipped_idx in skipped_indices:
                if len(selected_embeddings) >= highlights_count:
                    break

                logger.debug(
                    f"Creating a similar highlight with score {scores[skipped_idx]} for {entity_name} ({preprocessed_photos[skipped_idx].get('url')})..."
                )

                selected_photo_ids.append(preprocessed_photos[candidate_idx].get("id"))
                selected_embeddings.append(img_embeddings[skipped_idx])

            return selected_photo_ids
        except Exception as e:
            logger.error(
                f"Unable to create highlights for {entity_name}. Reason: {e}",
                exc_info=True,
            )
            return None

    @staticmethod
    def _preprocess_photo(p: dict) -> Optional[dict]:
        width, length = get_thumbnail_size()
        img = get_thumbnail(f"{p['url']}=w{width}-h{length}")
        if img is None:
            return None

        return {**p, "img": img}

    def get_handled_event_names(self) -> List[str]:
        return ["HighlightsSelectingTriggered"]

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

        years = [
            datetime.fromtimestamp(p.get("timestamp")).year for p in preprocessed_photos
        ]
        min_year = min(years) if years else 0
        max_year = max(years) if years else 1
        year_range = max_year - min_year if max_year > min_year else 1

        final_scores = []
        for i, p in enumerate(preprocessed_photos):
            base_quality = float(scores[i])
            time_bonus = (
                (datetime.fromtimestamp(p.get("timestamp")).year - min_year)
                / year_range
            ) * self.age_coeff
            final_scores.append(base_quality + time_bonus)

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
                    all_photos.extend(photos)

        return all_photos
