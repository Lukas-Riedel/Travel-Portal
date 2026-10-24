from datetime import UTC, datetime
from typing import Any

import pytz
from dateutil import parser

from src.errors.general_tool_error import GeneralToolError
from src.tools.base_tools import BaseTools


class TimeTools(BaseTools):
    def _get_description(self) -> str | None:
        return None

    def _get_supported_skill(self) -> str | None:
        return None

    def _get_tools(self) -> list[Any]:
        return [
            self.convert_datetime_to_epoch,
            self.convert_epoch_to_datetime,
            self.convert_seconds_to_duration,
            self.convert_timezone,
        ]

    def convert_seconds_to_duration(self, seconds: int) -> dict[str, Any]:
        """Converts a duration in seconds into hours, minutes, and seconds.
        Use this whenever presenting a duration that is provided in seconds.

        Args:
            seconds: Non-negative duration in seconds (e.g. 28800).

        Returns:
            A duration object containing:
            - hours (integer): Whole hours in the duration (e.g. 8).
            - minutes (integer): Remaining minutes after whole hours (e.g. 0).
            - seconds (integer): Remaining seconds after whole minutes (e.g. 0).
        """
        if seconds < 0:
            raise GeneralToolError("Duration in seconds cannot be negative.")

        return {
            "hours": seconds // 3600,
            "minutes": (seconds % 3600) // 60,
            "seconds": seconds % 60,
        }

    def convert_datetime_to_epoch(
        self,
        date_str: str,
        time_str: str,
        tz_name: str,
    ) -> dict[str, Any]:
        """Converts a given date (YYYY-MM-DD or DD.MM.YYYY) and time (HH:MM or HH:MM:SS) into a precise Unix epoch timestamp.
        Use this tool whenever creating tasks with deadlines, vouchers with expiration, or calculating precise timestamps.
        NEVER calculate epoch timestamps by mental math; use this tool to prevent calculation errors.

        Args:
            date_str (string): Date string in format 'YYYY-MM-DD' or 'D.M.YYYY' / 'DD.MM.YYYY' (e.g. '2026-04-15' or '15.4.2026').
            time_str (string): Time string in format 'HH:MM' or 'HH:MM:SS' (e.g. '23:55' or '18:30:00'). Default is '00:00:00'.
            tz_name (string): IANA timezone string (e.g., 'Europe/Prague', 'UTC', 'America/New_York'). Default is 'UTC'

        Returns:
            A dictionary containing:
            - epoch (integer): The computed Unix timestamp in seconds.
            - iso (string): ISO 8601 UTC representation of the timestamp (e.g. `"2026-04-15T23:55:00Z"`).
        """
        try:
            tz = pytz.timezone(tz_name)
        except (pytz.UnknownTimeZoneError, ValueError) as exc:
            raise GeneralToolError(f"Invalid timezone name '{tz_name}'.") from exc

        combined = f"{date_str.strip()} {time_str.strip()}"
        dayfirst = not date_str.strip()[:4].isdigit()

        try:
            dt_naive = parser.parse(timestr=combined, dayfirst=dayfirst)
        except (ValueError, TypeError) as exc:
            raise GeneralToolError(
                f"Could not parse date '{date_str}' and time '{time_str}'."
            ) from exc

        dt_with_tz = tz.localize(dt_naive)

        return {
            "epoch": int(dt_with_tz.timestamp()),
            "iso": dt_with_tz.astimezone(UTC).strftime("%Y-%m-%dT%H:%M:%SZ"),
        }

    def convert_epoch_to_datetime(
        self,
        epoch: int,
        tz_name: str,
    ) -> dict[str, Any]:
        """Converts a Unix epoch timestamp (seconds) into ISO 8601 and human-readable UTC components.

        Args:
            epoch: Unix epoch timestamp in seconds.
            tz_name (string): IANA timezone string (e.g., 'Europe/Prague', 'UTC', 'America/New_York'). Default is 'UTC'

        Returns:
            A dictionary containing:
            - iso (string): ISO 8601 UTC representation (e.g. `"2026-04-15T23:55:00Z"`).
            - date (string): DD.MM.YYYY (in the specified timezone).
            - time (string): HH:MM:SS (in the specified timezone).
            - utc_offset (string): UTC offset string (e.g. `"+0200"`).
        """
        try:
            tz = pytz.timezone(tz_name)
        except (pytz.UnknownTimeZoneError, ValueError) as exc:
            raise GeneralToolError(f"Invalid timezone name '{tz_name}'.") from exc

        dt_utc = datetime.fromtimestamp(epoch, tz=UTC)
        dt_with_tz = dt_utc.astimezone(tz)

        return {
            "iso": dt_utc.strftime("%Y-%m-%dT%H:%M:%SZ"),
            "date": dt_with_tz.strftime("%d.%m.%Y"),
            "time": dt_with_tz.strftime("%H:%M:%S"),
            "utc_offset": dt_with_tz.strftime("%z"),
        }

    def convert_timezone(
        self,
        datetime_str: str,
        target_tz: str,
        source_tz: str | None,
    ) -> dict[str, Any]:
        """Converts a datetime string or ISO 8601 string from one timezone to another target timezone.
        Use this tool whenever you receive timestamps/dates with offsets or from backend tools configured in home timezone and need to convert them to the user's current environment timezone or destination timezone.

        Args:
            datetime_str: Date/time string or ISO 8601 string (e.g. '2025-12-31T23:59:59+0200', '2026-04-15 14:30:00', '2026-04-15T12:30:00Z').
            target_tz: Target IANA timezone string (e.g. 'America/New_York', 'Europe/Prague', 'UTC').
            source_tz: Source IANA timezone if datetime_str does not contain an offset/timezone info. Default is None.

        Returns:
            A dictionary containing:
            - iso (string): ISO 8601 representation in target timezone with offset (e.g. `"2025-12-31T16:59:59-0500"`).
            - date (string): DD.MM.YYYY in the target timezone.
            - time (string): HH:MM:SS in the target timezone.
            - target_timezone: Target timezone name.
            - utc_offset (string): UTC offset string in target timezone (e.g. `"-0500"`).
            - epoch (integer): Unix timestamp in seconds.
        """
        try:
            target_zone = pytz.timezone(target_tz)
        except (pytz.UnknownTimeZoneError, ValueError) as exc:
            raise GeneralToolError(
                f"Invalid target timezone name '{target_tz}'."
            ) from exc

        dayfirst = not datetime_str.strip()[:4].isdigit()
        try:
            dt = parser.parse(timestr=datetime_str, dayfirst=dayfirst)
        except (ValueError, TypeError) as exc:
            raise GeneralToolError(
                f"Could not parse datetime '{datetime_str}'."
            ) from exc

        if dt.tzinfo is None:
            if source_tz:
                try:
                    source_zone = pytz.timezone(source_tz)
                    dt = source_zone.localize(dt)
                except (pytz.UnknownTimeZoneError, ValueError) as exc:
                    raise GeneralToolError(
                        f"Invalid source timezone name '{source_tz}'."
                    ) from exc
            else:
                dt = dt.replace(tzinfo=UTC)

        dt_target = dt.astimezone(target_zone)
        epoch = int(dt_target.timestamp())

        return {
            "iso": dt_target.strftime("%Y-%m-%dT%H:%M:%S%z"),
            "date": dt_target.strftime("%d.%m.%Y"),
            "time": dt_target.strftime("%H:%M:%S"),
            "target_timezone": target_tz,
            "utc_offset": dt_target.strftime("%z"),
            "epoch": epoch,
        }
