from datetime import datetime, timezone
from typing import Any
from zoneinfo import ZoneInfo, ZoneInfoNotFoundError

from dateutil import parser


class TimeTools:
    def get_tools(self) -> list[Any]:
        return [
            self.get_current_time,
            self.convert_datetime_to_epoch,
            self.convert_epoch_to_datetime,
            self.convert_timezone,
        ]

    def get_current_time(self, tz_name: str | None = None) -> dict[str, Any]:
        """Returns the current real-time UTC timestamp, ISO 8601 string, and date/time components (optionally converted to specified timezone).
        Always call this tool whenever you need to compute deadlines, relative dates, durations, or verify current year.

        Args:
            tz_name (string, optional): Optional IANA timezone string (e.g. 'Europe/Prague', 'America/New_York').

        Returns:
            A dictionary containing:
            - current_epoch (integer): Current Unix timestamp in seconds (UTC).
            - current_iso (string): Current ISO 8601 datetime (UTC), e.g. "2026-03-31T20:15:00Z".
            - current_year (integer): Current year (e.g. 2026).
            - current_date (string): Current date in YYYY-MM-DD format (UTC).
            - local_date (string, optional): Current date in YYYY-MM-DD format in specified timezone.
            - local_time (string, optional): Current time in HH:MM:SS format in specified timezone.
            - local_timezone (string, optional): Specified timezone name.
            - utc_offset (string, optional): UTC offset (e.g. "+0200").
        """
        now_utc = datetime.now(tz=timezone.utc)
        epoch = int(now_utc.timestamp())
        result: dict[str, Any] = {
            "current_epoch": epoch,
            "current_iso": now_utc.strftime("%Y-%m-%dT%H:%M:%SZ"),
            "current_year": now_utc.year,
            "current_date": now_utc.strftime("%Y-%m-%d"),
        }

        if tz_name:
            tz = ZoneInfo(tz_name)
            now_local = now_utc.astimezone(tz)
            result["local_date"] = now_local.strftime("%Y-%m-%d")
            result["local_time"] = now_local.strftime("%H:%M:%S")
            result["local_timezone"] = tz_name
            result["utc_offset"] = now_local.strftime("%z")

        return result

    def convert_datetime_to_epoch(
        self,
        date_str: str,
        time_str: str = "00:00:00",
        tz_name: str = "UTC",
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
            - iso (string): ISO 8601 UTC representation of the timestamp (e.g. "2026-04-15T23:55:00Z").
        """
        try:
            tz = ZoneInfo(tz_name)
        except (ZoneInfoNotFoundError, ValueError):
            return {"error": 400, "message": f"Invalid timezone name '{tz_name}'."}

        combined = f"{date_str.strip()} {time_str.strip()}"

        try:
            dt_naive = parser.parse(combined, dayfirst=True)
        except (ValueError, TypeError):
            return {"error": 400, "message": f"Could not parse date '{date_str}' and time '{time_str}'."}

        dt_with_tz = dt_naive.replace(tzinfo=tz)

        epoch = int(dt_with_tz.timestamp())
        iso = dt_with_tz.astimezone(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")

        return {
            "epoch": epoch,
            "iso": iso,
        }

    def convert_epoch_to_datetime(
        self, 
        epoch: int,
        tz_name: str = "UTC",
    ) -> dict[str, Any]:
        """Converts a Unix epoch timestamp (seconds) into ISO 8601 and human-readable UTC components.

        Args:
            epoch: Unix epoch timestamp in seconds.
            tz_name (string): IANA timezone string (e.g., 'Europe/Prague', 'UTC', 'America/New_York'). Default is 'UTC'

        Returns:
            A dictionary containing:
            - iso (string): ISO 8601 UTC representation (e.g. "2026-04-15T23:55:00Z").
            - date (string): YYYY-MM-DD (in the specified timezone).
            - time (string): HH:MM:SS (in the specified timezone).
            - utc_offset (string): UTC offset string (e.g. "+0200").
        """
        try:
            tz = ZoneInfo(tz_name)
        except (ZoneInfoNotFoundError, ValueError):
            return {"error": 400, "message": f"Invalid timezone name '{tz_name}'."}
        
        dt_utc = datetime.fromtimestamp(epoch, tz=timezone.utc)
        dt_with_tz = dt_utc.astimezone(tz)

        return {
            "iso": dt_utc.strftime("%Y-%m-%dT%H:%M:%SZ"),
            "date": dt_with_tz.strftime("%Y-%m-%d"),
            "time": dt_with_tz.strftime("%H:%M:%S"),
            "utc_offset": dt_with_tz.strftime("%z"),
        }

    def convert_timezone(
        self,
        datetime_str: str,
        target_tz: str,
        source_tz: str | None = None,
    ) -> dict[str, Any]:
        """Converts a datetime string or ISO 8601 string from one timezone to another target timezone.
        Use this tool whenever you receive timestamps/dates with offsets or from backend tools configured in home timezone and need to convert them to the user's current environment timezone or destination timezone.

        Args:
            datetime_str: Date/time string or ISO 8601 string (e.g. '2025-12-31T23:59:59+0200', '2026-04-15 14:30:00', '2026-04-15T12:30:00Z').
            target_tz: Target IANA timezone string (e.g. 'America/New_York', 'Europe/Prague', 'UTC').
            source_tz: Source IANA timezone if datetime_str does not contain an offset/timezone info. Default is None.

        Returns:
            A dictionary containing:
            - iso (string): ISO 8601 representation in target timezone with offset (e.g. '2025-12-31T16:59:59-0500').
            - date (string): YYYY-MM-DD in the target timezone.
            - time (string): HH:MM:SS in the target timezone.
            - target_timezone (string): Target timezone name.
            - utc_offset (string): UTC offset string in target timezone (e.g. '-0500').
            - epoch (integer): Unix timestamp in seconds.
        """
        try:
            target_zone = ZoneInfo(target_tz)
        except (ZoneInfoNotFoundError, ValueError):
            return {"error": 400, "message": f"Invalid target timezone name '{target_tz}'."}

        try:
            dt = parser.parse(datetime_str, dayfirst=True)
        except (ValueError, TypeError):
            return {"error": 400, "message": f"Could not parse datetime '{datetime_str}'."}

        if dt.tzinfo is None:
            if source_tz:
                try:
                    source_zone = ZoneInfo(source_tz)
                    dt = dt.replace(tzinfo=source_zone)
                except (ZoneInfoNotFoundError, ValueError):
                    return {"error": 400, "message": f"Invalid source timezone name '{source_tz}'."}
            else:
                dt = dt.replace(tzinfo=timezone.utc)

        dt_target = dt.astimezone(target_zone)
        epoch = int(dt_target.timestamp())

        return {
            "iso": dt_target.strftime("%Y-%m-%dT%H:%M:%S%z"),
            "date": dt_target.strftime("%Y-%m-%d"),
            "time": dt_target.strftime("%H:%M:%S"),
            "target_timezone": target_tz,
            "utc_offset": dt_target.strftime("%z"),
            "epoch": epoch,
        }
