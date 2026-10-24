import json
import math
from datetime import datetime
from typing import Any, Final

import pytz
from google import genai
from google.genai import types

from src.client.core_client import CoreClient
from src.core.generative_content_context import generative_content_environment
from src.core.logger import logger
from src.errors.missing_skills_error import MissingSkillsError
from src.skills.registry import SkillRegistry
from src.tools.registry import ToolRegistry

STRUCTURED_RESPONSE_CONTENT_TYPE: Final[str] = "application/json"
CHARS_PER_TOKEN_COUNT: Final[int] = 3
SOFT_TOKEN_LIMIT_RATIO: Final[float] = 0.75


class GenerativeContentEngine:
    def __init__(
        self,
        api_key: str,
        models: str,
        tool_registry: ToolRegistry,
        skill_registry: SkillRegistry,
        core_client: CoreClient,
        chat_timeout_ms: int,
        max_tool_result_tokens: int,
        max_cumulative_tokens: int,
    ) -> None:
        self.client: genai.Client = genai.Client(api_key=api_key)
        self.tool_registry: ToolRegistry = tool_registry
        self.skill_registry: SkillRegistry = skill_registry
        self.core_client: CoreClient = core_client
        self.models: list[str] = [m.strip() for m in models.split(",") if m.strip()]
        self.chat_timeout_ms: int = chat_timeout_ms
        self.max_tool_result_tokens: int = max_tool_result_tokens
        self.max_cumulative_tokens: int = max_cumulative_tokens
        logger.info(f"Loaded {len(self.models)} LLM model(s): {self.models}")

    def generate(
        self,
        messages: list[dict[str, Any]],
        schema: dict[str, Any] | None = None,
        enabled_skills: list[str] | None = None,
        allowed_skills: list[str] | None = None,
        context: str | None = None,
    ) -> tuple[str, list[dict[str, Any]], list[str], int, int]:
        return (
            self._do_generate(
                message=messages[-1].get("text", ""),
                history=[
                    types.Content.model_validate(message) for message in messages[:-1]
                ],
                schema=schema,
                enabled_skills=enabled_skills,
                allowed_skills=allowed_skills,
                context=context,
            )
            if messages
            else ("", [], enabled_skills or [], 0, 0)
        )

    def _do_generate(
        self,
        message: str | list[types.Part],
        history: list[types.Content],
        schema: dict[str, Any] | None = None,
        enabled_skills: list[str] | None = None,
        allowed_skills: list[str] | None = None,
        context: str | None = None,
        previous_iterations: int = 0,
        previous_total_prompt_tokens: int = 0,
        previous_total_candidate_tokens: int = 0,
    ) -> tuple[str, list[dict[str, Any]], list[str], int, int]:
        config = self._build_config(
            schema=schema,
            enabled_skills=enabled_skills,
            allowed_skills=allowed_skills,
            context=context,
        )

        for model in self.models:
            logger.debug(
                f"Attempting generative content request with model '{model}'...",
                extra={
                    "input": message,
                    "history": history,
                    "config": config.model_dump(
                        mode="json",
                        exclude_none=True,
                        by_alias=True,
                        fallback=str,
                    ),
                },
            )

            total_iterations = previous_iterations
            total_prompt_tokens = previous_total_prompt_tokens
            total_candidate_tokens = previous_total_candidate_tokens
            subagent_started = False

            try:
                chat = self.client.chats.create(
                    model=model,
                    history=list(history) if history else None,
                    config=config,
                )

                response = chat.send_message(message)
                total_iterations += 1

                # Process function calls requested by the model.
                while response.function_calls:
                    estimated_tokens_per_turn = 0
                    if response.usage_metadata:
                        estimated_tokens_per_turn = (
                            response.usage_metadata.prompt_token_count or 0
                        )
                        total_prompt_tokens += (
                            response.usage_metadata.prompt_token_count or 0
                        )
                        total_candidate_tokens += (
                            response.usage_metadata.candidates_token_count or 0
                        )

                    function_responses = []
                    start_subagent = False
                    subagent_skills = list(enabled_skills or [])

                    for fn_call in response.function_calls:
                        fn_name = fn_call.name
                        fn_args = fn_call.args or {}

                        try:
                            logger.debug(
                                f"Iteration {total_iterations}: Calling '{fn_name}({', '.join(f'{k}={repr(v)}' for k, v in fn_args.items())})'..."
                            )

                            fn_call_result = self.tool_registry.execute_tool(
                                name=fn_name, args=fn_args
                            )

                            estimated_tokens_per_call = math.ceil(
                                len(
                                    json.dumps(
                                        obj=fn_call_result,
                                        default=str,
                                        ensure_ascii=False,
                                    )
                                )
                                / CHARS_PER_TOKEN_COUNT
                            )

                            current_estimated_usage = (
                                total_prompt_tokens
                                + estimated_tokens_per_turn
                                + estimated_tokens_per_call
                            )

                            if estimated_tokens_per_call > self.max_tool_result_tokens:
                                error = (
                                    f"The tool call would consume roughly {estimated_tokens_per_call} input tokens "
                                    f"but the limit is {self.max_tool_result_tokens} input tokens per tool. "
                                    "Either provide filters to narrow down the results, or use a different tool."
                                )
                                logger.error(error)

                                function_responses.append(
                                    types.Part.from_function_response(
                                        name=fn_name,
                                        response={"error": error},
                                    )
                                )
                            elif current_estimated_usage > self.max_cumulative_tokens:
                                remaining_tokens = max(
                                    0,
                                    self.max_cumulative_tokens
                                    - estimated_tokens_per_turn
                                    - total_prompt_tokens,
                                )
                                error = (
                                    f"The tool call would consume roughly {estimated_tokens_per_call} input tokens "
                                    f"which would exceed the remaining window of {remaining_tokens} input tokens. "
                                    "Summarize your current findings and explain to the user that the token limit was reached, "
                                    "asking them to narrow down their request."
                                )
                                logger.error(error)

                                function_responses.append(
                                    types.Part.from_function_response(
                                        name=fn_name,
                                        response={"error": error},
                                    )
                                )
                            else:
                                response_payload = {"result": fn_call_result}

                                if (
                                    current_estimated_usage
                                    > SOFT_TOKEN_LIMIT_RATIO
                                    * self.max_cumulative_tokens
                                ):
                                    remaining_tokens = max(
                                        0,
                                        self.max_cumulative_tokens
                                        - current_estimated_usage,
                                    )
                                    warning = (
                                        f"You are approaching the context window limit with roughly {remaining_tokens} input tokens remaining. "
                                        "Do not invoke further tools unless absolutely necessary. Synthesize your final answer."
                                    )
                                    logger.warning(warning)
                                    response_payload["warning"] = warning

                                function_responses.append(
                                    types.Part.from_function_response(
                                        name=fn_name,
                                        response=response_payload,
                                    )
                                )
                                estimated_tokens_per_turn += estimated_tokens_per_call

                        except MissingSkillsError as exc:
                            start_subagent = True
                            subagent_skills.extend(exc.skills)

                            function_responses.append(
                                types.Part.from_function_response(
                                    name=fn_name,
                                    response={
                                        "result": {"enabled_skills": subagent_skills}
                                    },
                                )
                            )

                        except Exception as exc:
                            logger.exception(
                                f"An error ocurred when calling '{fn_name}({', '.join(f'{k}={repr(v)}' for k, v in fn_args.items())})'."
                            )

                            function_responses.append(
                                types.Part.from_function_response(
                                    name=fn_name, response={"error": str(exc)}
                                )
                            )

                    if start_subagent:
                        subagent_started = True
                        return self._do_generate(
                            message=function_responses,
                            history=chat.get_history(),
                            schema=schema,
                            enabled_skills=subagent_skills,
                            allowed_skills=allowed_skills,
                            context=context,
                            previous_iterations=total_iterations,
                            previous_total_prompt_tokens=total_prompt_tokens,
                            previous_total_candidate_tokens=total_candidate_tokens,
                        )

                    response = chat.send_message(function_responses)
                    total_iterations += 1

                if response.usage_metadata:
                    total_prompt_tokens += (
                        response.usage_metadata.prompt_token_count or 0
                    )
                    total_candidate_tokens += (
                        response.usage_metadata.candidates_token_count or 0
                    )

                # Extract the complete updated history from the chat session.
                updated_history = [
                    content.model_dump(mode="json", exclude_none=True, by_alias=True)
                    for content in chat.get_history()
                ]

                logger.info(
                    f"Generative content request succeeded with model '{model}'. Consumed {total_prompt_tokens} input tokens and {total_candidate_tokens} output tokens."
                )

                return (
                    response.text or "",
                    updated_history,
                    enabled_skills or [],
                    total_prompt_tokens,
                    total_candidate_tokens,
                )

            except Exception:
                if subagent_started:
                    raise

                logger.exception(
                    f"Model '{model}' could not process the generative content request."
                )

                continue

        raise RuntimeError(
            "All generative content models are currently unavailable. Please try again later."
        )

    def _build_config(
        self,
        schema: dict[str, Any] | None = None,
        enabled_skills: list[str] | None = None,
        allowed_skills: list[str] | None = None,
        context: str | None = None,
    ) -> types.GenerateContentConfig:
        config_kwargs = {}

        if enabled_skills is not None:
            enabled_set = set(enabled_skills)
            allowed_enabled_skills = (
                [skill for skill in allowed_skills if skill in enabled_set]
                if allowed_skills is not None
                else enabled_skills
            )

            parts = [
                part
                for part in [
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
                if part
            ]
            if parts:
                config_kwargs["system_instruction"] = "\n\n---\n\n".join(parts)

            tools = self.tool_registry.get_callable_tools(allowed_enabled_skills)
            if tools:
                config_kwargs["tools"] = tools

        if schema is not None:
            config_kwargs["response_mime_type"] = STRUCTURED_RESPONSE_CONTENT_TYPE
            config_kwargs["response_schema"] = schema

        return types.GenerateContentConfig(
            **config_kwargs,
            http_options=types.HttpOptions(timeout=self.chat_timeout_ms),
            automatic_function_calling=types.AutomaticFunctionCallingConfig(
                disable=True
            ),
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
        latitude, longitude = (
            env.get("latitude") if env else None,
            env.get("longitude") if env else None,
        )

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
