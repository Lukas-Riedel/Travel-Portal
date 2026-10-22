import contextvars

generative_content_environment: contextvars.ContextVar[dict | None] = (
    contextvars.ContextVar("generative_content_environment", default=None)
)

generative_content_skills: contextvars.ContextVar[list[str] | None] = (
    contextvars.ContextVar("generative_content_skills", default=None)
)
