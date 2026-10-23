from datetime import datetime
from typing import Any, Final

import pytz
from fastapi import HTTPException, status
from google import genai
from google.genai import types

from src.client.core_client import CoreClient
from src.core.generative_content_context import (
    generative_content_environment,
    generative_content_skills,
)
from src.core.logger import logger
from src.skills.registry import SkillRegistry
from src.tools.registry import ToolRegistry

CHAT_TIMEOUT_MS: Final[int] = 30000
MAX_ALLOWED_SKILLS_LOADING_PER_CHAT: Final[int] = 5


class GenerativeContentEngine:
    def __init__(
        self,
        api_key: str,
        models: str,
        tool_registry: ToolRegistry,
        skill_registry: SkillRegistry,
        core_client: CoreClient,
    ) -> None:
        self.client: genai.Client = genai.Client(api_key=api_key)
        self.tool_registry: ToolRegistry = tool_registry
        self.skill_registry: SkillRegistry = skill_registry
        self.core_client: CoreClient = core_client
        self.models: list[str] = [m.strip() for m in models.split(",") if m.strip()]
        logger.info(f"Loaded {len(self.models)} LLM model(s): {self.models}")

    def generate(
        self,
        messages: list[dict[str, Any]],
        schema: dict[str, Any] | None = None,
        allowed_skills: list[str] | None = None,
        context: str | None = None,
    ) -> tuple[str, list[dict[str, Any]], list[str]]:
        if not messages:
            return "", [], []

        # Validate and reconstruct complete history (including SDK structures and thought signatures).
        history = [types.Content.model_validate(msg) for msg in messages[:-1]]

        # The last message dictionary contains the new user turn/input.
        last_message = messages[-1].get("text", "")

        for _ in range(MAX_ALLOWED_SKILLS_LOADING_PER_CHAT):
            config_kwargs = {}

            if allowed_skills is not None:
                allowed_set = set(allowed_skills)
                enabled_skills = generative_content_skills.get() or []
                allowed_enabled_skills = [
                    skill for skill in enabled_skills if skill in allowed_set
                ]

                parts = [
                    p
                    for p in [
                        self.skill_registry.get_callable_skills(allowed_enabled_skills),
                        self._build_disabled_skills(
                            self.tool_registry.get_disabled_skills(
                                enabled_skills=allowed_enabled_skills,
                                allowed_skills=allowed_skills,
                            )
                        ),
                        self._build_environmental_context(),
                        self._build_personal_context(context),
                    ]
                    if p
                ]
                if parts:
                    config_kwargs["system_instruction"] = "\n\n---\n\n".join(parts)

                tools = self.tool_registry.get_callable_tools(allowed_enabled_skills)
                if tools:
                    config_kwargs["tools"] = tools

            if schema is not None:
                config_kwargs["response_mime_type"] = "application/json"
                config_kwargs["response_schema"] = schema

            config = types.GenerateContentConfig(
                **config_kwargs,
                http_options=types.HttpOptions(timeout=CHAT_TIMEOUT_MS),
            )

            restart_chat = False
            for model in self.models:
                logger.debug(
                    f"Attempting generative content request with model '{model}'..."
                )
                try:
                    chat = self.client.chats.create(
                        model=model,
                        history=list(history) if history else None,
                        config=config,
                    )

                    skills_before = len(generative_content_skills.get() or [])
                    response = chat.send_message(last_message)
                    skills_after = len(generative_content_skills.get() or [])

                    restart_chat = skills_before != skills_after
                    if restart_chat:
                        break

                    # Extract the complete updated history from the chat session.
                    updated_history = [
                        content.model_dump(
                            mode="json", exclude_none=True, by_alias=True
                        )
                        for content in chat.get_history()
                    ]

                    logger.info(
                        f"Generative content request succeeded with model '{model}'."
                    )
                    return (
                        response.text or "",
                        updated_history,
                        generative_content_skills.get() or [],
                    )

                except Exception as exc:
                    logger.warning(
                        f"Model '{model}' is unavailable ({exc}). Trying next model..."
                    )
                    continue

            if restart_chat:
                continue

            raise HTTPException(
                status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
                detail="All generative content models are currently unavailable. Please try again later.",
            )

        # TODO: Return something if the threshold for loading skills has been reached.

    def _build_personal_context(self, context: str | None) -> str | None:
        if not context:
            return None

        lines = ["# Authenticated User", context]

        return "\n".join(lines)

    def _build_disabled_skills(
        self, disabled_skills: list[str] | None = None
    ) -> str | None:
        if not disabled_skills:
            return None

        lines = ["# Disabled Skills"]
        for disabled_skill in disabled_skills:
            lines.append(
                f"- {disabled_skill}: {self.tool_registry.get_skill_description(disabled_skill)}"
            )

        return "\n".join(lines)

    def _build_environmental_context(self) -> str:
        env = generative_content_environment.get()

        timezone_name = env.get("timezone") if env else "UTC"
        latitude, longitude = env.get("latitude"), env.get("longitude")

        now = datetime.now(tz=pytz.timezone(timezone_name))
        lines = [
            "# Environmental Context",
            f"- Current date: {now.strftime('%A, %d %B %Y')}",
            f"- Current local time: {now.isoformat(timespec='seconds')} ({timezone_name})",
            f"- Current year: {now.year}",
        ]

        if latitude is not None and longitude is not None:
            lines.append(f"- Current coordinates: {latitude}, {longitude}")

            if resolved_address := self.core_client.get_address(
                latitude=latitude, longitude=longitude
            ):
                if (name := resolved_address.get("name")) and (
                    address := resolved_address.get("address")
                ):
                    lines.append(f"- Current address: {name} ({address})")

        return "\n".join(lines)
