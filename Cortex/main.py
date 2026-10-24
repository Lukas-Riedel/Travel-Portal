import os
from collections.abc import AsyncIterator
from contextlib import asynccontextmanager

from dotenv import load_dotenv
from fastapi import FastAPI, HTTPException, Request
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse, RedirectResponse
from starlette.exceptions import HTTPException as StarletteHTTPException

from src.api.authentication_middleware import AuthenticationContextMiddleware
from src.api.clustering_router import router as clustering_router
from src.api.embeddings_router import router as embeddings_router
from src.api.generative_content_router import router as generativecontent_router
from src.api.logging_middleware import LoggingMiddleware
from src.api.management_router import router as management_router
from src.client.core_client import CoreClient
from src.client.iam_client import IamClient
from src.core.clustering_engine import ClusteringEngine
from src.core.embeddings_engine import EmbeddingsEngine
from src.core.generative_content_engine import GenerativeContentEngine
from src.core.logger import logger, transaction_id
from src.tools.registry import SkillRegistry

load_dotenv()


@asynccontextmanager
async def lifespan(app: FastAPI) -> AsyncIterator[None]:
    app.state.embeddings_engine = EmbeddingsEngine(
        model_name=os.getenv("EMBEDDINGS_MODEL_NAME"),
        engine_device=os.getenv("ENGINE_DEVICE"),
    )
    app.state.clustering_engine = ClusteringEngine()
    app.state.iam_client = IamClient(
        iam_host=os.getenv("IAM_HOST"),
        iam_port=int(os.getenv("IAM_PORT")),
        cortex_client_id=os.getenv("CORTEX_CLIENT_ID"),
        cortex_client_secret=os.getenv("CORTEX_CLIENT_SECRET"),
    )
    app.state.core_client = CoreClient(
        core_host=os.getenv("CORE_HOST"),
        core_port=int(os.getenv("CORE_PORT")),
        iam_client=app.state.iam_client,
    )
    app.state.skill_registry = SkillRegistry(
        core_client=app.state.core_client,
    )
    app.state.generative_content_engine = GenerativeContentEngine(
        api_key=os.getenv("GEMINI_API_KEY"),
        models=os.getenv("GEMINI_MODELS"),
        skill_registry=app.state.skill_registry,
        core_client=app.state.core_client,
        chat_timeout_ms=int(os.getenv("CHAT_TIMEOUT_MS")),
        max_cumulative_tokens=int(os.getenv("MAX_CUMULATIVE_TOKENS")),
        max_tool_result_tokens=int(os.getenv("MAX_TOOL_RESULT_TOKENS")),
    )
    yield


app = FastAPI(
    title="Cortex API", docs_url="/swagger", version="1.0.0", lifespan=lifespan
)
app.include_router(management_router)
app.include_router(embeddings_router)
app.include_router(clustering_router)
app.include_router(generativecontent_router)
app.add_middleware(AuthenticationContextMiddleware)
app.add_middleware(LoggingMiddleware)


@app.get("/", include_in_schema=False)
async def redirect_to_swagger() -> RedirectResponse:
    return RedirectResponse(url="/swagger")


@app.exception_handler(StarletteHTTPException)
async def http_exception_handler(
    request: Request, exc: StarletteHTTPException
) -> JSONResponse:
    return await global_exception_handler(request=request, exc=exc)


@app.exception_handler(RequestValidationError)
async def validation_exception_handler(
    request: Request, exc: RequestValidationError
) -> JSONResponse:
    return await global_exception_handler(request=request, exc=exc)


@app.exception_handler(RuntimeError)
async def runtime_exception_handler(
    request: Request, exc: RuntimeError
) -> JSONResponse:
    return await global_exception_handler(request=request, exc=exc)


@app.exception_handler(Exception)
async def global_exception_handler(request: Request, exc: Exception) -> JSONResponse:
    status_code = 500
    error_type = exc.__class__.__name__
    message = str(exc)
    path = request.url.path
    t_id = transaction_id.get()

    if isinstance(exc, (HTTPException, StarletteHTTPException)):
        status_code = exc.status_code
        message = str(exc.detail) if hasattr(exc, "detail") else str(exc)

    error_content = {
        "code": status_code,
        "type": error_type,
        "message": message,
        "path": path,
    }

    logger.error(
        f"{error_type}: {message}",
        extra={
            "error": error_content,
            "transaction_id": t_id,
        },
    )

    return JSONResponse(status_code=status_code, content=error_content)
