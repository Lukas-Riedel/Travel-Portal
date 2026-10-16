import json
from typing import Any

from fastapi import APIRouter, Depends, Query, Request
from fastapi.responses import JSONResponse
from pydantic import BaseModel, ConfigDict, Field

from src.api.dependencies import require_backend_service_account
from src.core.generative_content_context import generative_content_environment
from src.core.logger import user_id

router = APIRouter(
    prefix="/generativecontent",
    tags=["Generative Content"],
    dependencies=[Depends(require_backend_service_account)],
)


class GenerativeContentRequest(BaseModel):
    model_config = ConfigDict(arbitrary_types_allowed=True)

    messages: list[dict[str, Any]] = Field(
        ...,
        description="Complete conversation history including response structures, tool calls, and a thought signature",
    )
    schema: dict[str, Any] | None = Field(
        None,
        description="Optional JSON Schema for structured output",
    )
    context: str | None = Field(
        None,
        description="Optional personal context prepended to the system instruction",
    )
    environment: dict[str, Any] | None = Field(
        None,
        description="Optional arbitrary JSON subtree with environment data passed to the generative engine",
    )


class GenerativeContentResponse(BaseModel):
    text: str | dict[str, Any] = Field(
        ...,
        description="Generated response",
    )
    history: list[dict[str, Any]] = Field(
        ...,
        description="New complete chat history",
    )


@router.post(
    "",
    response_model=GenerativeContentResponse,
    responses={
        400: {"description": "Bad Request"},
        401: {"description": "Unauthorized"},
        403: {"description": "Forbidden"},
        503: {"description": "Service Unavailable"},
    },
)
def generate_content(
    request: GenerativeContentRequest,
    req_obj: Request,
    use_skills: bool = Query(
        True,
        alias="useSkills",
        description="Whether to include skills and tools in the prompt context",
    ),
):
    token = generative_content_environment.set(request.environment)
    try:
        u_id = user_id.get()
        enabled_skills = req_obj.app.state.iam_client.get_user_roles(u_id) if use_skills and u_id else None

        text, history = req_obj.app.state.generative_content_engine.generate(
            request.messages,
            request.schema,
            enabled_skills,
            request.context,
        )
    finally:
        generative_content_environment.reset(token)

    return JSONResponse(
        content={
            "text": json.loads(text) if request.schema is not None else (text or ""),
            "history": history,
        }
    )