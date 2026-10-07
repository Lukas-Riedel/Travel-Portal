from datetime import datetime, timezone
from typing import Any, Final
from zoneinfo import ZoneInfo

from fastapi import HTTPException, status
from google import genai
from google.genai import types

from src.core.logger import logger
from src.core.skill_loader import SkillLoader
from src.tools.registry import ToolRegistry

CHAT_TIMEOUT_MS: Final[int] = 10000


class GenerativeContentEngine:
    def __init__(
        self,
        api_key: str,
        models: str,
        tool_registry: ToolRegistry,
        skill_loader: SkillLoader,
    ) -> None:
        self.client = genai.Client(api_key=api_key)
        self.tool_registry = tool_registry
        self.skill_loader = skill_loader
        self.system_instruction = self.skill_loader.load_skills()
        self.models = [m.strip() for m in models.split(",") if m.strip()]
        logger.info(f"Loaded {len(self.models)} LLM model(s): {self.models}")

    def generate(
        self,
        messages: list[dict[str, str]],
        schema: dict[str, Any] | None = None,
        use_skills: bool = True,
        context: str | None = None,
        timezone_name: str | None = None,
    ) -> str:
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

        config_kwargs: dict[str, Any] = {}
        if use_skills:
            parts = [p for p in [self._build_environmental_context(timezone_name), context, self.system_instruction] if p]
            if parts:
                config_kwargs["system_instruction"] = "\n\n---\n\n".join(parts)

            tools = self.tool_registry.get_callable_tools() if self.tool_registry else []
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
                    http_options=types.HttpOptions(timeout=CHAT_TIMEOUT_MS),
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

    def _build_environmental_context(self, timezone_name: str | None = None) -> str:
        now_utc = datetime.now(tz=timezone.utc)
        lines = [
            "## Environmental Context",
            f"Today is {now_utc.strftime('%A, %d %B %Y')}.",
        ]

        if timezone_name:
            user_tz = ZoneInfo(timezone_name)
            now_local = now_utc.astimezone(user_tz)
            lines.append(
                f"User's local time: {now_local.strftime('%Y-%m-%d %H:%M:%S')} (Timezone: {timezone_name}, UTC{now_local.strftime('%z')})."
            )

        lines.extend([
            f"Current UTC time: {now_utc.strftime('%Y-%m-%dT%H:%M:%SZ')}.",
            f"Current year: {now_utc.year}."
        ])
        
        return "\n".join(lines)