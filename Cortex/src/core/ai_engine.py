import threading
import base64
from io import BytesIO

import torch
from PIL import Image
from sentence_transformers import SentenceTransformer
from torch import Tensor


class AiEngine:
    def __init__(self, model_name: str, engine_device: str) -> None:
        self.model = SentenceTransformer(model_name, device=engine_device)

    def get_photo_embedding(self, base64_data: str) -> Tensor:
        image = self._get_thumbnail_image(base64_data)
        with torch.no_grad():
            return self._post_process_embedding(
                self.model.encode(image, convert_to_tensor=True, normalize_embeddings=True))

    def get_text_embedding(self, text: str) -> Tensor:
        with torch.no_grad():
            return self._post_process_embedding(
                self.model.encode([text], convert_to_tensor=True, normalize_embeddings=True))

    @staticmethod
    def _post_process_embedding(embedding: Tensor) -> Tensor:
        if embedding.ndimension() == 1:
            embedding = embedding.unsqueeze(0)

        return embedding.detach()

    def _get_thumbnail_image(self, base64_data: str) -> Image.Image:
        data = base64.b64decode(base64_data)
        img = Image.open(BytesIO(data)).convert("RGB")
        img.thumbnail(self._get_thumbnail_size(), Image.Resampling.LANCZOS)
        return img

    @staticmethod
    def _get_thumbnail_size() -> tuple[int, int]:
        # TODO: Make the size configurable in the deployment.
        return 512, 512
