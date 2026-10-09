from abc import ABC, abstractmethod
from datetime import datetime
from functools import wraps
from typing import Any
from zoneinfo import ZoneInfo

from src.core.generative_content_context import generative_content_environment
from src.core.logger import logger
from src.service.core_api_client import CoreApiClient


def _log_tool_call(fn):
    @wraps(fn)
    def wrapper(*args, **kwargs):
        param_names = fn.__code__.co_varnames[1:fn.__code__.co_argcount]
        positional = {k: v for k, v in zip(param_names, args[1:])}
        all_args = {**positional, **kwargs}
        formatted = ", ".join(f"{k}={repr(v)}" for k, v in all_args.items())
        logger.debug(
            f"Calling '{fn.__name__}({formatted})'",
            extra={"event": {"name": fn.__name__, "args": all_args}},
        )
        return fn(*args, **kwargs)
    return wrapper


class BaseTools(ABC):
    def __init_subclass__(cls, **kwargs):
        super().__init_subclass__(**kwargs)
        for attr_name, attr_value in cls.__dict__.items():
            if callable(attr_value) and not attr_name.startswith("_"):
                setattr(cls, attr_name, _log_tool_call(attr_value))

    @abstractmethod
    def get_tools(self) -> list[Any]:
        pass


class CoreApiTools(BaseTools):
    def __init__(self, core_api_client: CoreApiClient):
        self.core_api_client = core_api_client

    def _get_user_timezone(self) -> ZoneInfo:
        env = generative_content_environment.get()
        if env and env.get("timezone"):
            return ZoneInfo(env["timezone"])
        
        # System timezone is never returned if the request comes from UI.
        return self._get_system_timezone()

    def _get_system_timezone(self) -> ZoneInfo:
        config = self.core_api_client.get_configuration()
        return ZoneInfo(config["homeLocation"]["timezone"])

    def _epoch_to_iso(self, epoch: int | None, user_tz: ZoneInfo) -> str | None:
        if epoch is None:
            return None
        return datetime.fromtimestamp(epoch, tz=user_tz).strftime("%Y-%m-%dT%H:%M:%S%z")

    def _epoch_to_date(self, epoch: int | None, user_tz: ZoneInfo) -> str | None:
        if epoch is None:
            return None
        return datetime.fromtimestamp(epoch, tz=user_tz).strftime("%Y-%m-%d")
