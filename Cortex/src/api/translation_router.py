from fastapi import APIRouter, Request, Depends
from fastapi.params import Query

from src.api.dependencies import require_backend_service_account

router = APIRouter(
    tags=["Translation"],
    dependencies=[Depends(require_backend_service_account)],
)


@router.get(
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
def get_translation(req_obj: Request,
                    text: str = Query(..., description="Text to translate"),
                    source_language: str = Query(..., min_length=2, max_length=2, alias="sourceLanguage",
                                                 description="Code of the language that the text is written in"),
                    target_language: str = Query(..., min_length=2, max_length=2, alias="targetLanguage",
                                                 description="Code of the language to translate the text to")):
    return req_obj.app.state.translation_engine.translate(text, source_language, target_language)
