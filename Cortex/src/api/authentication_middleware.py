from collections.abc import Awaitable, Callable

from fastapi import Request, Response
from starlette.middleware.base import BaseHTTPMiddleware

from src.core.authentication_context import subject_token


class AuthenticationContextMiddleware(BaseHTTPMiddleware):
    async def dispatch(
        self, request: Request, call_next: Callable[[Request], Awaitable[Response]]
    ) -> Response:
        authorization = request.headers.get("Authorization", "")
        scheme, separator, credentials = authorization.partition(" ")
        access_token = (
            credentials.strip()
            if separator and scheme.lower() == "bearer" and credentials.strip()
            else None
        )

        token_subject = subject_token.set(access_token)
        try:
            return await call_next(request)
        finally:
            subject_token.reset(token_subject)
