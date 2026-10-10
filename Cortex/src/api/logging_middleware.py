import time
import uuid
from typing import Final

from fastapi import Request
from starlette.middleware.base import BaseHTTPMiddleware

from src.core.logger import logger, request_origin, transaction_id, user_id

TRANSACTION_ID_HEADER: Final[str] = "Transaction-Id"
REQUEST_ORIGIN_HEADER: Final[str] = "Request-Origin"
USER_ID_HEADER: Final[str] = "User-Id"


# TODO: Rename to something more generic, or create a new authentication middleware for setting User ID (and move the thread local variable from logger.py).
class LoggingMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next):
        start_time = time.perf_counter()

        t_id = request.headers.get(TRANSACTION_ID_HEADER) or str(uuid.uuid4())
        origin = request.headers.get(
            REQUEST_ORIGIN_HEADER, request.client.host if request.client else None
        )
        u_id = request.headers.get(USER_ID_HEADER)

        token_t = transaction_id.set(t_id)
        token_o = request_origin.set(origin)
        token_u = user_id.set(u_id)

        path = request.url.path
        if request.url.query:
            path += f"?{request.url.query}"

        if not path.startswith("/management"):
            logger.debug(f"Received the '{request.method} {path}' request...")

        try:
            response = await call_next(request)

            if not path.startswith("/management"):
                duration = round((time.perf_counter() - start_time) * 1000)
                logger.info(
                    f"The '{request.method} {path}' request was processed in {duration} milliseconds."
                )

            response.headers[TRANSACTION_ID_HEADER] = t_id
            return response

        finally:
            transaction_id.reset(token_t)
            request_origin.reset(token_o)
            user_id.reset(token_u)
