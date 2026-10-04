import json

from fastapi import APIRouter, Depends, Query, Request
from fastapi.responses import JSONResponse, PlainTextResponse, Response
from pydantic import BaseModel, ConfigDict, Field

from src.api.dependencies import require_backend_service_account

router = APIRouter(
    prefix="/generativecontent",
    tags=["Generative Content"],
    dependencies=[Depends(require_backend_service_account)],
)


class MessageTurn(BaseModel):
    role: str = Field(
        ...,
        description="The role of the message sender (e.g., 'user' or 'model')",
        example="user",
    )
    text: str = Field(
        ...,
        description="The text content of the message turn",
        example="What is the capital of France?",
    )


class GenerativeContentRequest(BaseModel):
    model_config = ConfigDict(arbitrary_types_allowed=True)

    messages: list[MessageTurn] = Field(
        ...,
        description="The list of conversation message turns to send to the generative model",
    )
    schema: dict | None = Field(
        None,
        description="Optional JSON Schema for structured output",
    )


@router.post(
    "",
    response_class=Response,
    responses={
        400: {"description": "Bad Request", "model": None},
        401: {"description": "Unauthorized", "model": None},
        403: {"description": "Forbidden", "model": None},
        503: {"description": "Service Unavailable", "model": None},
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
    text: str = req_obj.app.state.generative_content_engine.generate(
        [message.model_dump() for message in request.messages],
        request.schema,
        use_skills,
    )

    if request.schema is not None:
        return JSONResponse(content=json.loads(text))

    return PlainTextResponse(content=text)