import contextvars
from contextvars import ContextVar

subject_token: ContextVar[str | None] = contextvars.ContextVar(
    "subject_token", default=None
)
