from typing import List

from fastapi import APIRouter, Request, Depends
from fastapi.params import Query
from pydantic import BaseModel, Field

from src.api.dependencies import require_backend_service_account

router = APIRouter(
    prefix="/embeddings",
    tags=["Embeddings"],
    dependencies=[Depends(require_backend_service_account)],
)


class TextEmbeddingRequest(BaseModel):
    data: str = Field(
        ...,
        description="The text to embed",
        example="This is the text that will be converted to an AI-generated vector.",
    )


class PhotoEmbeddingRequest(BaseModel):
    data: str = Field(
        ...,
        description="The base64-encoded photo to embed",
        example="SGVsbG8gd29ybGQ=.",
    )


class EmbeddingResponse(BaseModel):
    embedding: List[float] = Field(
        ..., description="The AI-generated vector", examples=[[0.1, 0.2, 0.3]]
    )
    dimensions: int = Field(
        ...,
        description="The number of dimensions in the AI-generated vector",
        examples=[3],
    )


@router.post(
    "/text",
    response_model=EmbeddingResponse,
    responses={
        400: {"description": "Bad Request", "model": None},
        401: {"description": "Unauthorized", "model": None},
        403: {"description": "Forbidden", "model": None},
        422: {"description": "Unprocessable Entity", "model": None},
    },
)
def get_text_embedding(request: TextEmbeddingRequest, req_obj: Request,
                       language: str = Query(min_length=2, max_length=2, description="Language code")):
    translated_text = req_obj.app.state.translation_engine.translate(request.data, source_language=language)
    embedding = req_obj.app.state.ai_engine.get_text_embedding(translated_text).flatten()

    return {"embedding": [float(x) for x in embedding], "dimensions": len(embedding)}


@router.post(
    "/photo",
    response_model=EmbeddingResponse,
    responses={
        400: {"description": "Bad Request", "model": None},
        401: {"description": "Unauthorized", "model": None},
        403: {"description": "Forbidden", "model": None},
        422: {"description": "Unprocessable Entity", "model": None},
    },
)
def get_photo_embedding(request: PhotoEmbeddingRequest, req_obj: Request):
    embedding = req_obj.app.state.ai_engine.get_photo_embedding(request.data).flatten()

    return {"embedding": [float(x) for x in embedding], "dimensions": len(embedding)}
