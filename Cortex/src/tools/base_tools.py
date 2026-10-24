import sys
import time
from abc import ABC, abstractmethod
from datetime import datetime, tzinfo
from typing import Any

import pytz

from src.client.core_client import CoreClient
from src.core.generative_content_context import generative_content_environment
from src.errors.general_tool_error import GeneralToolError


class BaseTools(ABC):
    def _verify_error_response(self, obj: Any) -> None:
        if isinstance(obj, dict) and bool(obj.get("message")):
            raise GeneralToolError(obj.get("message"))

    def _extract_topic(
        self, topic_ids: list[str], data: dict[str, Any]
    ) -> dict[str, Any]:
        return {"_topic_ids": topic_ids, "_data": data}

    @abstractmethod
    def _get_tools(self) -> list[Any]:
        pass

    @abstractmethod
    def _get_supported_skill(self) -> str | None:
        pass

    @abstractmethod
    def _get_description(self) -> str | None:
        pass


class BaseCoreTools(BaseTools):
    def __init__(self, core_client: CoreClient) -> None:
        self.core_client: CoreClient = core_client

    def _get_user_timezone(self) -> tzinfo:
        env = generative_content_environment.get()
        if env and env.get("timezone"):
            return pytz.timezone(env["timezone"])

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

    def _get_system_timezone(self) -> tzinfo:
        config = self.core_client.get_configuration()
        return pytz.timezone(config["homeLocation"]["timezone"])

    def _epoch_to_iso(self, epoch: int | None, user_tz: tzinfo) -> str | None:
        if epoch is None:
            return None

        return datetime.fromtimestamp(epoch, tz=user_tz).strftime("%Y-%m-%dT%H:%M:%S%z")

    def _epoch_to_date(self, epoch: int | None, user_tz: tzinfo) -> str | None:
        if epoch is None:
            return None

        return datetime.fromtimestamp(epoch, tz=user_tz).strftime("%d.%m.%Y")

    def _epoch_to_time(self, epoch: int | None, user_tz: tzinfo) -> str | None:
        if epoch is None:
            return None

        return datetime.fromtimestamp(epoch, tz=user_tz).strftime("%H:%M")
