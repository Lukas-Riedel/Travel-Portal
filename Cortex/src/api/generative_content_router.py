import json
from typing import Any

from fastapi import APIRouter, Depends, Request
from fastapi.responses import JSONResponse
from pydantic import BaseModel, ConfigDict, Field

from src.api.dependencies import get_current_user, require_trusted_service_account
from src.core.generative_content_context import (
    generative_content_environment,
    generative_content_skills,
)

router = APIRouter(
    prefix="/generativecontent",
    tags=["Generative Content"],
    dependencies=[Depends(require_trusted_service_account)],
)


class GenerativeContentRequest(BaseModel):
    model_config = ConfigDict(arbitrary_types_allowed=True)

    messages: list[dict[str, Any]] = Field(
        default=...,
        description="Complete conversation history including response structures, tool calls, and a thought signature",
    )
    schema: dict[str, Any] | None = Field(
        default=None,
        description="Optional JSON Schema for structured output",
    )
    context: str | None = Field(
        default=None,
        description="Optional personal context prepended to the system instruction",
    )
    skills: list[str] | None = Field(
        default=None,
        description="Optional list with explicitly enabled skills",
    )
    environment: dict[str, Any] | None = Field(
        default=None,
        description="Optional arbitrary JSON subtree with environment data passed to the generative engine",
    )


class GenerativeContentResponse(BaseModel):
    text: str | dict[str, Any] = Field(
        default=...,
        description="Generated response",
    )
    history: list[dict[str, Any]] = Field(
        default=...,
        description="New complete chat history",
    )
    skills: list[str] = Field(
        default=...,
        description="Enabled skills",
    )


@router.post(
    path="",
    response_model=GenerativeContentResponse,
    responses={
        400: {"description": "Bad Request"},
        401: {"description": "Unauthorized"},
        403: {"description": "Forbidden"},
    },
)
def generate_content(
    request: GenerativeContentRequest,
    req_obj: Request,
    current_user: dict[str, Any] = Depends(get_current_user),
) -> JSONResponse:
    token_environment = generative_content_environment.set(request.environment)
    token_skills = generative_content_skills.set(request.skills)
    try:
        u_id = current_user.get("user_id")
        allowed_skills = (
            req_obj.app.state.iam_client.get_user_roles(u_id)
            if request.skills and u_id
            else None
        )

        text, history, skills = req_obj.app.state.generative_content_engine.generate(
            messages=request.messages,
            schema=request.schema,
            allowed_skills=allowed_skills,
            context=request.context,
        )
    finally:
        generative_content_environment.reset(token_environment)
        generative_content_skills.reset(token_skills)

    return JSONResponse(
        content={
            "text": json.loads(text) if request.schema is not None else (text or ""),
            "history": history,
            "skills": skills,
        }
    )
