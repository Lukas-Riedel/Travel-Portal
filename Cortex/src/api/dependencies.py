import os
from typing import Any

from fastapi import Depends, HTTPException, Request, status
from fastapi.security import HTTPAuthorizationCredentials, HTTPBearer

security = HTTPBearer()


def get_current_user(
    request: Request, token: HTTPAuthorizationCredentials = Depends(security)
) -> dict[str, Any]:
    iam_client = request.app.state.iam_client

    return iam_client.authenticate(token=token.credentials)


def require_allowlisted_service_account(
    request: Request, user_info: dict[str, Any] = Depends(get_current_user)
) -> None:
    allowed_clients = [
        client
        for client in os.getenv("ALLOWLISTED_SERVICE_CLIENTS").split(",")
        if client
    ]
    if user_info.get("client") not in allowed_clients:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail=f"The user '{user_info.get('user_id')}' is not authorized to access this resource.",
        )
