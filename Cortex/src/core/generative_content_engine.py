from typing import Any, Final

from fastapi import HTTPException, status
from google import genai
from google.genai import types

from src.core.logger import logger

MODEL_NAME: Final[str] = "gemini"
MODEL_WHITELIST: Final[list[str]] = ["flash", "pro"]
MODEL_BLACKLIST: Final[list[str]] = ["image", "latest", "preview", "omni", "tts", "transcribe"]
GENERATE_CONTENT_ACTION: Final[str] = "generatecontent"


class GenerativeContentEngine:
    def __init__(self, api_key: str) -> None:
        self._client = genai.Client(api_key=api_key)
        self._models = self._load_models()
        logger.info(f"Loaded {len(self._models)} LLM model(s): {self._models}")

    def _load_models(self) -> list[str]:
        candidates: list[str] = []

        for model in self._client.models.list():
            name: str = model.name

            if MODEL_NAME not in name:
                continue
            if not any(w in name for w in MODEL_WHITELIST):
                continue
            if any(b in name for b in MODEL_BLACKLIST):
                continue

            supported = [a.lower() for a in (model.supported_actions or [])]
            if GENERATE_CONTENT_ACTION not in supported:
                continue

            candidates.append(name)

        candidates.sort(key=lambda n: [int(x) if x.isdigit() else x for x in n.replace("-", ".").split(".")], reverse=True)
        return candidates

    def generate(self, prompt: str, schema: dict[str, Any] | None = None) -> str:
        config = types.GenerateContentConfig(
            automatic_function_calling=types.AutomaticFunctionCallingConfig(disable=True),
            **({"response_mime_type": "application/json", "response_schema": schema} if schema is not None else {}),
        )

        for model in self._models:
            logger.debug(f"Attempting generative content request with model '{model}'...")
            try:
                response = self._client.models.generate_content(
                    model=model,
                    contents=prompt,
                    config=config,
                )
                logger.info(f"Generative content request succeeded with model '{model}'.")
                return response.text
            except Exception as exc:
                logger.warning(f"Model '{model}' is unavailable ({exc}). Trying next model...")
                continue

        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="All generative content models are currently unavailable. Please try again later.",
        )
