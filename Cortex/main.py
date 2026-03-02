import os
import threading
import uvicorn
import traceback
from fastapi import FastAPI, Request, HTTPException
from fastapi.responses import JSONResponse, RedirectResponse
from fastapi.exceptions import RequestValidationError
from starlette.exceptions import HTTPException as StarletteHTTPException
from dotenv import load_dotenv
from src.core.core_client import CoreClient
from src.core.distributed_cache import DistributedCache
from src.core.ai_engine import AiEngine
from src.api.management_router import router as management_router
from src.api.embeddings_router import router as embeddings_router
from src.api.clustering_router import router as clustering_router
from src.service.authentication_service import AuthenticationService
from src.api.logging_middleware import LoggingMiddleware
from src.core.logger import logger, transaction_id

load_dotenv()

app = FastAPI(title="Cortex API", docs_url="/swagger", version="1.0.0")
app.include_router(management_router)
app.include_router(embeddings_router)
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
        "path": path
    }
    
    logger.error(
        f"{error_type}: {message}", 
        extra={
            "error": error_content,
            "stacktrace": traceback.format_exc().splitlines(),
            "transaction_id": t_id
        }
    )

    return JSONResponse(
        status_code=status_code,
        content=error_content
    )

def run_webserver():
    uvicorn.run(app, host="0.0.0.0", port=8080, log_level="info")

def main():
    distributed_cache = DistributedCache(
        os.getenv("REDIS_HOST"),
        int(os.getenv("REDIS_PORT")),
        os.getenv("REDIS_PASSWORD"),
        os.getenv("REDIS_SSL", "false").lower() == "true",
    )
    ai_engine = AiEngine(
        distributed_cache,
        os.getenv("MODEL_NAME"),
        os.getenv("ENGINE_DEVICE"),
        float(os.getenv("CONTENT_COEFFICIENT")),
        float(os.getenv("NEGATIVE_COEFFICIENT")),
        float(os.getenv("CLUSTER_COEFFICIENT")),
    )

    authentication_service = AuthenticationService(
        os.getenv("IAM_HOST"),
        int(os.getenv("IAM_PORT")),
        os.getenv("IAM_APP_CLIENT_ID")
    )

    app.state.ai_engine = ai_engine
    app.state.authentication_service = authentication_service

    uvicorn.run(app, host="0.0.0.0", port=8080, log_level="info")

if __name__ == "__main__":
    main()
