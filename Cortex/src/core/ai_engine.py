import torch
import os
import gc
from PIL import Image
from sentence_transformers import SentenceTransformer, util
from typing import List, Union, Final, Optional
from torch import Tensor
import numpy as np
from src.core.logger import logger
from src.core.distributed_cache import DistributedCache
from src.utils.image_utils import get_thumbnail

torch.set_num_threads(int(os.getenv("MAX_THREADS")))
torch.set_num_interop_threads(int(os.getenv("MAX_THREADS")))

MODEL_NAME: Final[str] = os.getenv("MODEL_NAME")
ENGINE_DEVICE: Final[str] = os.getenv("ENGINE_DEVICE")

PHOTO_EMBEDDINGS_CACHE_KEY_FORMAT = "AiEngine:PhotoEmbedding:{model_name}:{photo_id}"
PHOTO_EMBEDDINGS_CACHE_TTL: Final[int] = 365 * 86400


class AiEngine:
    def __init__(
        self,
        distributed_cache: DistributedCache,
        content_coeff: float,
        negative_coeff: float,
    ) -> None:
        self.model = SentenceTransformer(MODEL_NAME, device=ENGINE_DEVICE)
        self.distributed_cache = distributed_cache
        self.content_coeff = content_coeff
        self.negative_coeff = negative_coeff

    def extract_style_context(self, photos: List[dict]) -> Optional[Tensor]:
        style_embeddings = []

        if photos:
            for photo in photos:
                cache_key = PHOTO_EMBEDDINGS_CACHE_KEY_FORMAT.format(
                    model_name=MODEL_NAME, photo_id=photo.get("id")
                )

                cached_emb = self.distributed_cache.get(
                    cache_key, PHOTO_EMBEDDINGS_CACHE_TTL
                )

                if cached_emb:
                    emb_tensor = torch.from_numpy(
                        np.frombuffer(cached_emb, dtype=np.float32).copy()
                    ).to(ENGINE_DEVICE)

                    style_embeddings.append(emb_tensor)
                else:
                    try:
                        url = photo.get("url")
                        logger.debug(f"Computing an embedding for the '{url}' photo...")
                        img = get_thumbnail(url)
                        if img is None:
                            continue

                        with torch.no_grad():
                            emb = self.model.encode(img, convert_to_tensor=True)
                            gc.collect()

                        emb_storage = emb.cpu().numpy().astype(np.float32).tobytes()
                        self.distributed_cache.set(
                            cache_key, emb_storage, PHOTO_EMBEDDINGS_CACHE_TTL
                        )

                        style_embeddings.append(emb)
                    except Exception as e:
                        logger.warning(
                            f"An error occurred when processing '{url}': {e}"
                        )

        if not style_embeddings:
            return None

        combined_style = torch.mean(torch.stack(style_embeddings), dim=0)
        combined_style = combined_style / combined_style.norm(dim=-1, keepdim=True)
        return combined_style.unsqueeze(0)

    def get_image_embedding(
        self, pil_images: Union[Image.Image, List[Image.Image]]
    ) -> Tensor:
        with torch.no_grad():
            res = self.model.encode(pil_images, convert_to_tensor=True)
            gc.collect()
            return res

    def get_text_embedding(self, text: str) -> Tensor:
        with torch.no_grad():
            res = self.model.encode([text], convert_to_tensor=True)
            gc.collect()
            return res

    def calculate_max_similarity(
        self, candidate_embedding: Tensor, existing_embeddings: List[Tensor]
    ) -> float:
        sims = util.cos_sim(candidate_embedding, torch.stack(existing_embeddings))[0]
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
