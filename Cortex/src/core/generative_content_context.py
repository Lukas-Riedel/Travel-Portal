import contextvars

generative_content_environment: contextvars.ContextVar[dict | None] = contextvars.ContextVar("generative_content_environment", default=None)
