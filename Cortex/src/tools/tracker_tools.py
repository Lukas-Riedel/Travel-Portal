import math
from datetime import datetime
from typing import Any, Final

from src.tools.base_tools import BaseCoreTools

HOURS_PER_MAN_DAY: Final[int] = 8
BALANCE_EVENT_TYPES: Final[tuple[str, ...]] = (
    "overtime",
    "vacation",
    "selfcare",
    "tenure",
)


class TrackerReadTools(BaseCoreTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to query the current time off balances (overtime, vacation, selfcare days, and tenure) and the already planned time off from the time tracker in Travel Portal. Activate and apply this skill when the user asks how many days of vacation, selfcare days, overtime, or tenure they have available, or when their time off is planned (e.g. "how many vacation days do I have left?", "what is my overtime balance?", "when do I have my vacation planned?").'

    def _get_supported_skill(self) -> str:
        return "tracker.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_time_off_balances,
            self.get_planned_time_off,
        ]

    def _get_working_hours_per_day(self) -> float:
        config = self.core_client.get_configuration()
        return HOURS_PER_MAN_DAY * config["timeTracking"]["currentFte"]

    def get_time_off_balances(self) -> dict[str, Any]:
        """Retrieves the current time off balances from the time tracker in Travel Portal.
        Use this when the user asks how much vacation, sick days, overtime, or tenure they have available -
        for example "how many vacation days do I have left?", "what is my overtime balance?".

        Returns:
            An object containing the available balance in whole working days (rounded down, already
            adjusted to the user's FTE) for each of the following types. A negative value means more
            time was taken than available:
            - overtime (integer): Overtime balance in days.
            - vacation (integer): Vacation balance in days.
            - selfcare (integer): Sick days (selfcare) balance in days.
            - tenure (integer): Tenure balance in days.
        """
        working_hours_per_day = self._get_working_hours_per_day()

        balances = {}
        for event_type in BALANCE_EVENT_TYPES:
            events = self.core_client.get_time_tracking_events(event_type=event_type)
            self._verify_error_response(events)

            balance = (
                events[0].get("balance", 0)
                if isinstance(events, list) and events
                else 0
            )
            balances[event_type] = math.floor(balance / working_hours_per_day + 1e-10)

        return balances

    def get_planned_time_off(self) -> list[dict[str, Any]]:
        """Retrieves the time off that is already planned (from today onwards) in the time tracker in Travel Portal.
        Use this when the user asks when or how much of their vacation, selfcare days, overtime, or tenure is already
        planned - for example "when do I have my vacation planned?", "do I have any time off planned next month?".

        Returns:
            A list of planned time off objects sorted by the date ascending (earliest first), where each object contains:
            - type (string): Type of the time off. One of `overtime`, `vacation`, `selfcare`, `tenure`.
            - date (string): Date of the planned time off in DD.MM.YYYY format.
            - days (number): Length of the planned time off in working days (already adjusted to the user's FTE, can be fractional, e.g. `0.5`).
        """
        working_hours_per_day = self._get_working_hours_per_day()

        events = self.core_client.get_time_tracking_events()
        self._verify_error_response(events)

        user_tz = self._get_user_timezone()
        start_of_today = int(
            datetime.now(user_tz)
            .replace(hour=0, minute=0, second=0, microsecond=0)
            .timestamp()
        )
        maximum_timestamp = self._get_maximum_timestamp()

        planned = [
            event
            for event in events
            if event.get("type") in BALANCE_EVENT_TYPES
            and event.get("hours", 0) < 0
            and start_of_today <= event.get("timestamp", 0) <= maximum_timestamp
        ]
        planned.sort(key=lambda event: event["timestamp"])

        return [
            {
                "type": event["type"],
                "date": self._epoch_to_date(epoch=event["timestamp"], user_tz=user_tz),
                "days": round(-event["hours"] / working_hours_per_day, 2),
            }
            for event in planned
        ]
