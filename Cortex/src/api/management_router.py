import os

from fastapi import APIRouter, Request

router = APIRouter(prefix="/management", tags=["Management"], include_in_schema=False)


@router.get("/liveness")
def check_liveness():
    return {
        "name": os.getenv("APP_NAME"),
        "version": os.getenv("VERSION_TAG"),
        "status": "up",
    }


@router.get("/readiness")
def check_readiness(request: Request):
    return {
        "name": os.getenv("APP_NAME"),
        "version": os.getenv("VERSION_TAG"),
        "status": "up",
        "dependencies": {},
    }
