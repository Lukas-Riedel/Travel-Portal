from datetime import datetime
from typing import Any, Final

import pytz
from fastapi import HTTPException, status
from google import genai
from google.genai import types

from src.client.core_client import CoreClient
from src.core.generative_content_context import generative_content_environment
from src.core.logger import logger
from src.skills.registry import SkillRegistry
from src.tools.registry import ToolRegistry

CHAT_TIMEOUT_MS: Final[int] = 30000


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
        enabled_skills: list[str] | None = None,
        context: str | None = None,
    ) -> tuple[str, list[dict[str, Any]]]:
        if not messages:
            return "", []

        # Validate and reconstruct complete history (including SDK structures and thought signatures)
        history = [types.Content.model_validate(msg) for msg in messages[:-1]]

        # The last message dictionary contains the new user turn/input
        last_message = messages[-1].get("text", "")

        config_kwargs = {}

        if allowed_skills is not None:
            allowed_set = set(allowed_skills)
            enabled_skills_by_default = (
                self.tool_registry.get_enabled_by_default_skills(allowed_skills)
            )
            all_enabled_skills = list(
                dict.fromkeys(
                    skill
                    for skill in [
                        *enabled_skills_by_default,
                        *(enabled_skills or []),
                    ]
                    if skill in allowed_set
                )
            )

            parts = [
                p
                for p in [
                    self.skill_registry.get_callable_skills(all_enabled_skills),
                    self._build_disabled_skills(
                        self.tool_registry.get_disabled_skills(
                            enabled_skills=all_enabled_skills,
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

            tools = self.tool_registry.get_callable_tools(all_enabled_skills)
            if tools:
                config_kwargs["tools"] = tools

        if schema is not None:
            config_kwargs["response_mime_type"] = "application/json"
            config_kwargs["response_schema"] = schema

        config = types.GenerateContentConfig(
            **config_kwargs,
            http_options=types.HttpOptions(timeout=CHAT_TIMEOUT_MS),
        )

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
                response = chat.send_message(last_message)

                # Extract the complete updated history from the chat session.
                updated_history = [
                    content.model_dump(mode="json", exclude_none=True, by_alias=True)
                    for content in chat.get_history()
                ]

                logger.info(
                    f"Generative content request succeeded with model '{model}'."
                )
                return response.text or "", updated_history

            except Exception as exc:
                logger.warning(
                    f"Model '{model}' is unavailable ({exc}). Trying next model..."
                )
                continue

        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="All generative content models are currently unavailable. Please try again later.",
        )

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
