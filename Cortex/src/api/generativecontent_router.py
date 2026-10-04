import json

from fastapi import APIRouter, Depends, Request
from fastapi.responses import JSONResponse, PlainTextResponse, Response
from pydantic import BaseModel, ConfigDict, Field

from src.api.dependencies import require_backend_service_account

router = APIRouter(
    prefix="/generativecontent",
    tags=["Generative Content"],
    dependencies=[Depends(require_backend_service_account)],
)


class GenerativeContentRequest(BaseModel):
    model_config = ConfigDict(arbitrary_types_allowed=True)

    prompt: str = Field(
        ...,
        description="The prompt to send to the generative model",
        example="What is the capital of France?",
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
):
    text: str = req_obj.app.state.generative_content_engine.generate(
        request.prompt,
        request.schema,
    )

    if request.schema is not None:
        return JSONResponse(content=json.loads(text))

    return PlainTextResponse(content=text)