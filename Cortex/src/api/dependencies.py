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


def require_backend_service_account(
    request: Request, user_info: dict[str, Any] = Depends(get_current_user)
) -> None:
    if user_info.get("client") != os.getenv("IAM_BACKEND_CLIENT_ID"):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail=f"The user '{user_info.get('user_id')}' is not authorized to access this resource.",
        )
