import os

from fastapi import Depends, HTTPException, Request, status
from fastapi.security import HTTPAuthorizationCredentials, HTTPBearer

from src.client.iam_client import IamClient

security = HTTPBearer()


def get_current_user(
    request: Request, token: HTTPAuthorizationCredentials = Depends(security)
):
    iam_client: IamClient = (
        request.app.state.iam_client
    )

    return iam_client.authenticate(token.credentials)


def require_backend_service_account(
    request: Request, user_info: dict = Depends(get_current_user)
):
    if user_info.get("client") != os.getenv("IAM_BACKEND_CLIENT_ID"):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail=f"The user '{user_info.get('user_id')}' is not authorized to access this resource.",
        )
