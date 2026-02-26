import os
from fastapi import APIRouter, Request, HTTPException, Depends
from src.api.dependencies import require_backend_service_account

router = APIRouter(prefix="/management", tags=["Management"], include_in_schema=False)

@router.get("/liveness")
def check_liveness():
    return {
        "name": os.getenv("APP_NAME"),
        "version": os.getenv("VERSION_TAG"),
        "status": "up"
    }

@router.get("/readiness")
def check_readiness(request: Request):
    request.app.state.ai_engine.get_text_embedding("Readiness Check")

    # TODO: Include Redis/RMQ if it will stay in the service after refactoring.
    return {
        "name": os.getenv("APP_NAME"),
        "version": os.getenv("VERSION_TAG"),
        "status": "up",
        "dependencies": {}
    }