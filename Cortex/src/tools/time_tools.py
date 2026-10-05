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
        ]

    def get_current_time(self) -> dict[str, Any]:
        """Returns the current real-time UTC timestamp, human-readable ISO 8601 string, and current year/month/day.
        Always call this tool whenever you need to compute deadlines, relative dates, durations, or verify current year.

        Returns:
            A dictionary containing:
            - current_epoch (integer): Current Unix timestamp in seconds (UTC).
            - current_iso (string): Current ISO 8601 datetime (UTC), e.g. "2026-03-31T20:15:00Z".
            - current_year (integer): Current year (e.g. 2026).
            - current_date (string): Current date in YYYY-MM-DD format (UTC).
        """
        now = datetime.now(tz=timezone.utc)
        epoch = int(now.timestamp())
        return {
            "current_epoch": epoch,
            "current_iso": now.strftime("%Y-%m-%dT%H:%M:%SZ"),
            "current_year": now.year,
            "current_date": now.strftime("%Y-%m-%d"),
        }

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
