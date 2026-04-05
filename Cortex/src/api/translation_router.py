from fastapi import APIRouter, Request, Depends
from fastapi.params import Query
from pydantic import BaseModel, Field

from src.api.dependencies import require_backend_service_account

router = APIRouter(
    tags=["Translation"],
    dependencies=[Depends(require_backend_service_account)],
)


class TranslationRequest(BaseModel):
    data: str = Field(
        ...,
        description="The text to translate",
        example="This is the text that will be translated.",
    )


@router.post(
    "/translate",
    response_model=str,
    response_description="A translated text",
    responses={
        400: {"description": "Bad Request", "model": None},
        401: {"description": "Unauthorized", "model": None},
        403: {"description": "Forbidden", "model": None},
        422: {"description": "Unprocessable Entity", "model": None},
    },
)
def get_translation(
    request: TranslationRequest,
    req_obj: Request,
    source_language: str = Query(
        ...,
        min_length=2,
        max_length=2,
        alias="sourceLanguage",
        description="Code of the language that the text is written in",
        example="en",
    ),
    target_language: str = Query(
        ...,
        min_length=2,
        max_length=2,
        alias="targetLanguage",
        description="Code of the language to translate the text to",
        example="cs",
    ),
):
    return req_obj.app.state.translation_engine.translate(
        request.data, source_language, target_language
    )
