from typing import Any, Final

from fastapi import HTTPException, status
from google import genai
from google.genai import types

from src.core.logger import logger
from src.core.skill_loader import SkillLoader
from src.tools.registry import ToolRegistry

MODEL_NAME: Final[str] = "gemini"
MODEL_WHITELIST: Final[list[str]] = ["flash", "pro"]
MODEL_BLACKLIST: Final[list[str]] = ["image", "latest", "preview", "omni", "tts", "transcribe"]
GENERATE_CONTENT_ACTION: Final[str] = "generatecontent"
class GenerativeContentEngine:
    def __init__(
        self,
        api_key: str,
        tool_registry: ToolRegistry | None = None,
        skill_loader: SkillLoader | None = None,
    ) -> None:
        self.client = genai.Client(api_key=api_key)
        self.tool_registry = tool_registry
        self.skill_loader = skill_loader or SkillLoader()
        self.system_instruction = self.skill_loader.load_skills()
        self.models = self.load_models()
        logger.info(f"Loaded {len(self.models)} LLM model(s): {self.models}")

    def load_models(self) -> list[str]:
        candidates: list[str] = []

        for model in self.client.models.list():
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

    def generate(self, messages: list[dict[str, str]], schema: dict[str, Any] | None = None) -> str:
        if not messages:
            return ""

        # Previous messages form the history, last message is sent to chat
        history: list[types.Content] = [
            types.Content(
                role=msg["role"],
                parts=[types.Part.from_text(text=msg["text"])],
            )
            for msg in messages[:-1]
        ]
        last_message = messages[-1]["text"]

        tools = self.tool_registry.get_callable_tools() if self.tool_registry else []

        config_kwargs: dict[str, Any] = {}
        if self.system_instruction:
            config_kwargs["system_instruction"] = self.system_instruction

        if tools:
            config_kwargs["tools"] = tools

        if schema is not None:
            config_kwargs["response_mime_type"] = "application/json"
            config_kwargs["response_schema"] = schema

        config = types.GenerateContentConfig(**config_kwargs)

        for model in self.models:
            logger.debug(f"Attempting generative content request with model '{model}'...")
            try:
                chat = self.client.chats.create(
                    model=model,
                    history=list(history) if history else None,
                    config=config,
                )
                response = chat.send_message(last_message)
                logger.info(f"Generative content request succeeded with model '{model}'.")
                return response.text or ""
            except Exception as exc:
                logger.warning(f"Model '{model}' is unavailable ({exc}). Trying next model...")
                continue

        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="All generative content models are currently unavailable. Please try again later.",
        )
