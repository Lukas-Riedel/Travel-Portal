from typing import List

from fastapi import APIRouter, Request, Depends
from pydantic import BaseModel, Field

from src.api.dependencies import require_backend_service_account

router = APIRouter(
    prefix="/clustering",
    tags=["Clustering"],
    dependencies=[Depends(require_backend_service_account)],
)


class EmbeddingsClusteringRequest(BaseModel):
    embeddings: List[List[float]] = Field(
        ...,
        description="List of embeddings to cluster",
        examples=[[[0.1, 0.2, 0.3], [0.4, 0.2, 0.9], [0.3, 0.6, 0.3]]],
    )
    clusters: int = Field(..., description="Count of clusters to create", examples=[2])


@router.post(
    "/embeddings",
    response_model=List[List[int]],
    response_description="A list of clusters, where each cluster is a list of indices pointing to the original embeddings array",
    responses={
        200: {"content": {"application/json": {"example": [[0, 2, 4], [1, 3], [5]]}}},
        400: {"description": "Bad Request", "model": None},
        401: {"description": "Unauthorized", "model": None},
        403: {"description": "Forbidden", "model": None},
        422: {"description": "Unprocessable Entity", "model": None},
    },
)
def get_embeddings_clusters(request: EmbeddingsClusteringRequest, req_obj: Request):
    clusters = req_obj.app.state.clustering_engine.get_embeddings_clusters(
        request.embeddings, request.clusters
    )

    return clusters
