import os
import traceback
from contextlib import asynccontextmanager

from dotenv import load_dotenv
from fastapi import FastAPI, Request, HTTPException
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse, RedirectResponse
from starlette.exceptions import HTTPException as StarletteHTTPException

from src.api.clustering_router import router as clustering_router
from src.api.embeddings_router import router as embeddings_router
from src.api.logging_middleware import LoggingMiddleware
from src.api.management_router import router as management_router
from src.api.translation_router import router as translation_router
from src.core.ai_engine import AiEngine
from src.core.clustering_engine import ClusteringEngine
from src.core.logger import logger, transaction_id
from src.core.translation_engine import TranslationEngine
from src.service.authentication_service import AuthenticationService

load_dotenv()


@asynccontextmanager
async def lifespan(app: FastAPI):
    app.state.ai_engine = AiEngine(os.getenv("EMBEDDINGS_MODEL_NAME"), os.getenv("ENGINE_DEVICE"))
    app.state.clustering_engine = ClusteringEngine()
    app.state.translation_engine = TranslationEngine(
        model_name_format=os.getenv("TRANSLATION_MODEL_NAME_FORMAT"),
        supported_languages={
            lang.strip()
            for lang in os.getenv("SUPPORTED_LANGUAGES", "").split(",")
            if lang.strip()
        },
        device=os.getenv("ENGINE_DEVICE"),
    )
    app.state.authentication_service = AuthenticationService(
        os.getenv("IAM_HOST"),
        int(os.getenv("IAM_PORT", 8080)),
        os.getenv("IAM_APP_CLIENT_ID"),
    )
    yield


app = FastAPI(
    title="Cortex API", docs_url="/swagger", version="1.0.0", lifespan=lifespan
)
app.include_router(management_router)
app.include_router(embeddings_router)
app.include_router(translation_router)
app.include_router(clustering_router)
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
            "stacktrace": traceback.format_exc().splitlines(),
            "transaction_id": t_id,
        },
    )

    return JSONResponse(status_code=status_code, content=error_content)
