import torch
import os
import gc
import math
from PIL import Image
from sentence_transformers import SentenceTransformer, util
from typing import List, Union, Final, Optional
from torch import Tensor
import numpy as np
from sklearn.cluster import AgglomerativeClustering
from src.core.logger import logger
from src.core.distributed_cache import DistributedCache
from src.utils.image_utils import get_thumbnail, get_thumbnail_image

torch.set_num_threads(int(os.getenv("MAX_THREADS")))
torch.set_num_interop_threads(int(os.getenv("MAX_THREADS")))

PHOTO_EMBEDDING_CACHE_KEY_FORMAT = "AiEngine:PhotoEmbedding:{model_name}:{photo_id}"
PHOTO_EMBEDDING_CACHE_TTL: Final[int] = 365 * 86400

PHOTO_CHECKSUM_CACHE_KEY_FORMAT = "AiEngine:PhotoChecksum:{photo_id}"
PHOTO_CHECKSUM_CACHE_TTL: Final[int] = 365 * 86400

ATTRIBUTES_ESTIMATION_NEAREST_NEIGHBOURS_COUNT: Final[int] = 9

ATTRIBUTE_EXPONENT: Final[dict] = {
    "composition": 4,
    "sky": 10,
    "shadows": 10,
    "circumstances": 4,
    "atmosphere": 10
}
DEFAULT_EXPONENT: Final[int] = 10

class AiEngine:
    def __init__(
        self,
        distributed_cache: DistributedCache,
        model_name: str,
        engine_device: str,
        content_coeff: float,
        negative_coeff: float,
        cluster_coeff: float,
    ) -> None:
        self.model = SentenceTransformer(model_name, device=engine_device)
        self.model_name = model_name
        self.engine_device = engine_device
        self.distributed_cache = distributed_cache
        self.content_coeff = content_coeff
        self.negative_coeff = negative_coeff
        self.cluster_coeff = cluster_coeff

    def estimate_attributes_from_references(
        self,
        candidate_embedding: Tensor,
        reference_highlights: List[dict],
    ) -> dict:
        if not reference_highlights:
            return {}
        
        if candidate_embedding.ndimension() == 1:
            candidate_embedding = candidate_embedding.unsqueeze(0)

        embeddings_with_data = []
        for h in reference_highlights:
            emb = self.get_or_create_photo_embedding(h.get("photo"))
            if emb is not None:
                embeddings_with_data.append((emb, h))

        if not embeddings_with_data:
            return {}

        ref_stack = torch.stack([item[0].squeeze() for item in embeddings_with_data])
        ref_data = [item[1] for item in embeddings_with_data]

        sims = util.cos_sim(candidate_embedding, ref_stack)[0]
        actual_k = min(ATTRIBUTES_ESTIMATION_NEAREST_NEIGHBOURS_COUNT, len(sims))
        top_k_values, top_k_indices = torch.topk(sims, actual_k)

        all_keys = set()
        for idx in top_k_indices:
            attrs = ref_data[idx.item()].get("attributes", {})
            if attrs:
                all_keys.update(attrs.keys())

        sum_weights = torch.sum(top_k_values).item()
        if sum_weights == 0:
            return {}

        final_attrs = {}
        for key in all_keys:
            weighted_sum = 0.0
            sum_weights_for_key = 0.0

            for i, idx in enumerate(top_k_indices):
                neighbor_attrs = ref_data[idx.item()].get("attributes", {})

                if key in neighbor_attrs:
                    val = neighbor_attrs[key]
                    weight = pow(top_k_values[i].item(), ATTRIBUTE_EXPONENT.get(key, DEFAULT_EXPONENT))

                    weighted_sum += val * weight
                    sum_weights_for_key += weight

            if sum_weights_for_key > 0:
                final_attrs[key] = int(round(weighted_sum / sum_weights_for_key))

        return final_attrs

    def extract_style_context(self, photos: List[dict]) -> Optional[Tensor]:
        style_embeddings = []

        if photos:
            for photo in photos:
                emb = self.get_or_create_photo_embedding(photo)
                if emb is not None:
                    style_embeddings.append(emb)

        if not style_embeddings:
            return None

        safe_style_embs = [e.squeeze(0) if e.ndimension() == 2 else e for e in style_embeddings]
        combined_style = torch.mean(torch.stack(safe_style_embs), dim=0)
        combined_style = combined_style / combined_style.norm(dim=-1, keepdim=True)
        return combined_style.unsqueeze(0)

    def cluster_embeddings(self, embeddings: Tensor) -> List[int]:
        emb_np = embeddings.cpu().numpy()
        
        clusterer = AgglomerativeClustering(
            n_clusters=max(1, min(int(math.sqrt(len(embeddings)) * self.cluster_coeff), len(embeddings))),
            distance_threshold=None,
            linkage="average",
            metric="cosine"
        )
        return clusterer.fit_predict(emb_np).tolist()

    def get_image_embedding(
        self, pil_images: Union[Image.Image, List[Image.Image]]
    ) -> Tensor:
        with torch.no_grad():
            res = self.model.encode(pil_images, convert_to_tensor=True, normalize_embeddings=True)
            
            if res.ndimension() == 1:
                res = res.unsqueeze(0)
            
            return res

    def get_or_create_photo_embedding(self, photo: dict) -> Optional[Tensor]:
        photo_id = photo.get("id")
        embedding_cache_key = PHOTO_EMBEDDING_CACHE_KEY_FORMAT.format(
            model_name=self.model_name, photo_id=photo_id
        )
        checksum_cache_key = PHOTO_CHECKSUM_CACHE_KEY_FORMAT.format(photo_id=photo_id)

        cached_emb = self.distributed_cache.get(
            embedding_cache_key, PHOTO_EMBEDDING_CACHE_TTL
        )
        cached_checksum = self.distributed_cache.get(
            checksum_cache_key, PHOTO_CHECKSUM_CACHE_TTL
        )
        actual_checksum = self.get_photo_checksum(photo)

        if cached_emb and cached_checksum == actual_checksum:
            emb = torch.from_numpy(
                np.frombuffer(cached_emb, dtype=np.float32).copy()
            ).to(self.engine_device)
            return emb.unsqueeze(0) if emb.ndimension() == 1 else emb

        try:
            url = photo.get("url")
            logger.debug(f"Computing an embedding for the '{url}' photo...")
            img = get_thumbnail(url)
            if img is None:
                return None

            emb = self.get_image_embedding(img)
            emb_storage = emb.cpu().numpy().astype(np.float32).tobytes()
            self.distributed_cache.set(
                embedding_cache_key, emb_storage, PHOTO_EMBEDDING_CACHE_TTL
            )
            self.distributed_cache.set(
                checksum_cache_key, actual_checksum, PHOTO_CHECKSUM_CACHE_TTL
            )

            return emb
        except Exception as e:
            logger.warning(
                f"An error occurred when processing '{photo.get('url')}': {e}"
            )
            return None

    def get_photo_embedding(self, base64_data: str) -> Tensor:
        img = get_thumbnail_image(base64_data)
        emb = self.get_image_embedding(img)
        return emb.detach()

    def get_text_embedding(self, text: str) -> Tensor:
        with torch.no_grad():
            res = self.model.encode([text], convert_to_tensor=True, normalize_embeddings=True)
            return res

    def calculate_max_similarity(
        self, candidate_embedding: Tensor, existing_embeddings: List[Tensor]
    ) -> float:
        if candidate_embedding.ndimension() == 1:
            candidate_embedding = candidate_embedding.unsqueeze(0)
            
        safe_embeddings = [e.squeeze(0) if e.ndimension() == 2 else e for e in existing_embeddings]
        sims = util.cos_sim(candidate_embedding, torch.stack(safe_embeddings))[0]
        return float(torch.max(sims))

    def calculate_scores(
        self,
        content_emb: Tensor,
        img_embeddings: Tensor,
        style_vector: Optional[Tensor] = None,
        negative_emb: Optional[Tensor] = None,
    ) -> Tensor:
        with torch.no_grad():
            total_score = util.cos_sim(img_embeddings, content_emb).flatten()

            if style_vector is not None:
                style_sim = util.cos_sim(img_embeddings, style_vector).flatten()
                total_score = (total_score * self.content_coeff) + (
                    style_sim * (1.0 - self.content_coeff)
                )

            if negative_emb is not None:
                neg_sim = util.cos_sim(img_embeddings, negative_emb).flatten()
                total_score = total_score - (neg_sim * self.negative_coeff)

            return total_score

    def get_photo_checksum(self, photo: dict) -> str:
        # Use the photo permalink as its checksum -> if the photo changes, its permalink changes, too.
        return photo.get("permalink")
