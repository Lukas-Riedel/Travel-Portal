import base64
from io import BytesIO

import torch
from PIL import Image
from sentence_transformers import SentenceTransformer
from torch import Tensor


class EmbeddingsEngine:
    def __init__(self, model_name: str, engine_device: str) -> None:
        self.model: SentenceTransformer = SentenceTransformer(
            model_name_or_path=model_name, device=engine_device
        )
        self.max_text_tokens: int = self.model[
            0
        ].model.config.text_config.max_position_embeddings

    def get_photo_embedding(self, base64_data: str) -> Tensor:
        image = self._get_thumbnail_image(base64_data)
        with torch.no_grad():
            return self._post_process_embedding(
                self.model.encode(
                    image, convert_to_tensor=True, normalize_embeddings=True
                )
            )

    def get_text_embedding(self, text: str) -> Tensor:
        with torch.no_grad():
            return self._post_process_embedding(
                self.model.encode(
                    [self._truncate_text(text)],
                    convert_to_tensor=True,
                    normalize_embeddings=True,
                )
            )

    def _truncate_text(self, text: str) -> str:
        # TODO: This is only a workaround. CLIP's text encoder accepts at most
        # `max_position_embeddings` tokens (77) and longer texts make it fail
        # with a ValueError (older sentence-transformers versions don't truncate
        # CLIP inputs on their own), so they get silently truncated here. The
        # callers (Core) should be fixed so that they never send such long
        # texts, e.g. the concatenated negative terms used when selecting
        # highlights. For highlight selection, consider dropping the negative
        # terms (or text inputs in general) altogether.
        tokenizer = self.model.tokenizer.tokenizer
        encoding = tokenizer(
            text=text, truncation=True, max_length=self.max_text_tokens
        )
        return tokenizer.decode(
            token_ids=encoding["input_ids"], skip_special_tokens=True
        )

    def _post_process_embedding(self, embedding: Tensor) -> Tensor:
        if embedding.ndimension() == 1:
            embedding = embedding.unsqueeze(0)

        return embedding.detach()

    def _get_thumbnail_image(self, base64_data: str) -> Image.Image:
        data = base64.b64decode(base64_data)
        img = Image.open(BytesIO(data)).convert("RGB")
        img.thumbnail(
            size=self._get_thumbnail_size(), resample=Image.Resampling.LANCZOS
        )
        return img

    def _get_thumbnail_size(self) -> tuple[int, int]:
        # TODO: Make the size configurable in the deployment.
        return 512, 512
