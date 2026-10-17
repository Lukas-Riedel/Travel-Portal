from fastapi import APIRouter, Depends, Request
from pydantic import BaseModel, Field

from src.api.dependencies import require_backend_service_account

router = APIRouter(
    prefix="/clustering",
    tags=["Clustering"],
    dependencies=[Depends(require_backend_service_account)],
)


class EmbeddingsClusteringRequest(BaseModel):
    embeddings: list[list[float]] = Field(
        default=...,
        description="List of embeddings to cluster",
        examples=[[[0.1, 0.2, 0.3], [0.4, 0.2, 0.9], [0.3, 0.6, 0.3]]],
    )
    clusters: int = Field(
        default=..., description="Count of clusters to create", examples=[2]
    )


@router.post(
    path="/embeddings",
    response_model=list[list[int]],
    response_description="A list of clusters, where each cluster is a list of indices pointing to the original embeddings array",
    responses={
        200: {"content": {"application/json": {"example": [[0, 2, 4], [1, 3], [5]]}}},
        400: {"description": "Bad Request", "model": None},
        401: {"description": "Unauthorized", "model": None},
        403: {"description": "Forbidden", "model": None},
        422: {"description": "Unprocessable Entity", "model": None},
    },
)
def get_embeddings_clusters(
    request: EmbeddingsClusteringRequest, req_obj: Request
) -> list[list[int]]:
    clusters = req_obj.app.state.clustering_engine.get_embeddings_clusters(
        embeddings=request.embeddings, clusters_count=request.clusters
    )

    return clusters
