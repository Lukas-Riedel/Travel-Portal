import time
import uuid
from typing import Final

from fastapi import Request
from starlette.middleware.base import BaseHTTPMiddleware

from src.core.logger import logger, transaction_id, request_origin

TRANSACTION_ID_HEADER: Final[str] = "Transaction-Id"
REQUEST_ORIGIN_HEADER: Final[str] = "Request-Origin"


class LoggingMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next):
        start_time = time.perf_counter()

        t_id = request.headers.get(TRANSACTION_ID_HEADER) or str(uuid.uuid4())
        origin = request.headers.get(
            REQUEST_ORIGIN_HEADER, request.client.host if request.client else None
        )

        token_t = transaction_id.set(t_id)
        token_o = request_origin.set(origin)

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
