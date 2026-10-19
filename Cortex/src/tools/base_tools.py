import sys
import time
from abc import ABC, abstractmethod
from collections.abc import Callable
from datetime import datetime
from functools import wraps
from typing import Any
from zoneinfo import ZoneInfo

from src.client.core_client import CoreClient
from src.core.generative_content_context import generative_content_environment
from src.core.logger import logger


def _log_tool_call(fn: Callable[..., Any]) -> Callable[..., Any]:
    @wraps(fn)
    def wrapper(*args: Any, **kwargs: Any) -> Any:
        param_names = fn.__code__.co_varnames[1 : fn.__code__.co_argcount]
        positional = dict(zip(param_names, args[1:], strict=False))
        all_args = {**positional, **kwargs}
        formatted = ", ".join(f"{k}={repr(v)}" for k, v in all_args.items())
        logger.debug(
            f"Calling '{fn.__name__}({formatted})'",
            extra={"event": {"name": fn.__name__, "args": all_args}},
        )
        return fn(*args, **kwargs)

    return wrapper


class BaseTools(ABC):
    def __init_subclass__(cls, **kwargs: Any) -> None:
        super().__init_subclass__(**kwargs)
        for attr_name, attr_value in cls.__dict__.items():
            if callable(attr_value) and not attr_name.startswith("_"):
                setattr(cls, attr_name, _log_tool_call(attr_value))

    def _is_error_response(self, obj: Any) -> bool:
        return isinstance(obj, dict) and bool(obj.get("message"))

    @abstractmethod
    def _get_tools(self) -> list[Any]:
        pass

    @abstractmethod
    def _get_supported_skill(self) -> str | None:
        pass


class BaseCoreTools(BaseTools):
    def __init__(self, core_client: CoreClient) -> None:
        self.core_client: CoreClient = core_client

    def _get_user_timezone(self) -> ZoneInfo:
        env = generative_content_environment.get()
        if env and env.get("timezone"):
            return ZoneInfo(env["timezone"])

        return self._get_system_timezone()

    def _get_maximum_timestamp(self) -> int:
        env = generative_content_environment.get()
        if env and env.get("maximumTimestamp"):
            maximum_timestamp = int(env.get("maximumTimestamp"))
            if not self._can_read_future():
                return min(maximum_timestamp, int(time.time()))
            return maximum_timestamp

        return sys.maxsize

    # TODO: Remove from the environment, obtain from the user's roles.
    def _can_read_future(self) -> bool:
        env = generative_content_environment.get()
        if env is not None:
            return bool(env.get("canReadFuture", True))

        return True

    def _is_trip_visible(self, trip: dict[str, Any]) -> bool:
        if trip.get("year") is None:
            return self._can_read_future()

        return self._get_maximum_timestamp() >= trip.get("start", 0)

    def _get_system_timezone(self) -> ZoneInfo:
        config = self.core_client.get_configuration()
        return ZoneInfo(config["homeLocation"]["timezone"])

    def _epoch_to_iso(self, epoch: int | None, user_tz: ZoneInfo) -> str | None:
        if epoch is None:
            return None

        return datetime.fromtimestamp(epoch, tz=user_tz).strftime("%Y-%m-%dT%H:%M:%S%z")

    def _epoch_to_date(self, epoch: int | None, user_tz: ZoneInfo) -> str | None:
        if epoch is None:
            return None

        return datetime.fromtimestamp(epoch, tz=user_tz).strftime("%Y-%m-%d")

    def _epoch_to_time(self, epoch: int | None, user_tz: ZoneInfo) -> str | None:
        if epoch is None:
            return None

        return datetime.fromtimestamp(epoch, tz=user_tz).strftime("%H:%M")
