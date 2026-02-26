import os
from fastapi import Depends, HTTPException, status, Request
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from src.service.authentication_service import AuthenticationService

security = HTTPBearer()

def get_current_user(request: Request, token: HTTPAuthorizationCredentials = Depends(security)):
    authentication_service: AuthenticationService = request.app.state.authentication_service

    return authentication_service.authenticate(token.credentials)

def require_backend_service_account(request: Request, user_info: dict = Depends(get_current_user)):
    auth_service: AuthenticationService = request.app.state.authentication_service

    if user_info.get("client") != os.getenv("IAM_BACKEND_CLIENT_ID"):
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN,detail=f"The user '{user_info.get('user_id')}' is not authorized to access this resource.")