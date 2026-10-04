import os
import traceback
from contextlib import asynccontextmanager

from dotenv import load_dotenv
from fastapi import FastAPI, HTTPException, Request
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse, RedirectResponse
from starlette.exceptions import HTTPException as StarletteHTTPException

from src.api.clustering_router import router as clustering_router
from src.api.embeddings_router import router as embeddings_router
from src.api.generativecontent_router import router as generativecontent_router
from src.api.logging_middleware import LoggingMiddleware
from src.api.management_router import router as management_router
from src.core.clustering_engine import ClusteringEngine
from src.core.embeddings_engine import EmbeddingsEngine
from src.core.generative_content_engine import GenerativeContentEngine
from src.core.logger import logger, transaction_id
from src.core.skill_loader import SkillLoader
from src.service.authentication_service import AuthenticationService
from src.service.core_api_client import CoreApiClient
from src.tools.registry import ToolRegistry

load_dotenv()


@asynccontextmanager
async def lifespan(app: FastAPI):
    app.state.embeddings_engine = EmbeddingsEngine(
        os.getenv("EMBEDDINGS_MODEL_NAME"),
        os.getenv("ENGINE_DEVICE"),
    )
    app.state.clustering_engine = ClusteringEngine()
    app.state.authentication_service = AuthenticationService(
        os.getenv("IAM_HOST"),
        int(os.getenv("IAM_PORT")),
        os.getenv("IAM_APP_CLIENT_ID"),
        os.getenv("IAM_BACKEND_CLIENT_ID"),
        os.getenv("IAM_BACKEND_CLIENT_SECRET"),
    )
    app.state.core_api_client = CoreApiClient(
        os.getenv("CORE_HOST"),
        int(os.getenv("CORE_PORT")),
        app.state.authentication_service,
    )
    app.state.tool_registry = ToolRegistry(
        app.state.core_api_client,
    )
    app.state.skill_loader = SkillLoader()
    app.state.generative_content_engine = GenerativeContentEngine(
        os.getenv("GEMINI_API_KEY"),
        tool_registry=app.state.tool_registry,
        skill_loader=app.state.skill_loader,
    )
    yield


app = FastAPI(
    title="Cortex API", docs_url="/swagger", version="1.0.0", lifespan=lifespan
)
app.include_router(management_router)
app.include_router(embeddings_router)
app.include_router(clustering_router)
app.include_router(generativecontent_router)
app.add_middleware(LoggingMiddleware)


@app.get("/", include_in_schema=False)
async def redirect_to_swagger():
    return RedirectResponse(url="/swagger")


@app.exception_handler(StarletteHTTPException)
async def http_exception_handler(request: Request, exc: StarletteHTTPException):
    return await global_exception_handler(request, exc)


@app.exception_handler(RequestValidationError)
async def validation_exception_handler(request: Request, exc: RequestValidationError):
    return await global_exception_handler(request, exc)


@app.exception_handler(Exception)
async def global_exception_handler(request: Request, exc: Exception):
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
