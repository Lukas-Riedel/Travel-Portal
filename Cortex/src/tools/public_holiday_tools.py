from abc import ABC
from datetime import date, datetime, time, timedelta
from html import unescape
from typing import Any

import requests
from dateutil.rrule import rruleset, rrulestr
from icalendar import Calendar

from src.tools.base_tools import BaseCoreTools

REQUEST_TIMEOUT = (5, 15)


class BasePublicHolidayTools(BaseCoreTools, ABC):
    def _format_holiday(
        self,
        name: str,
        category: str,
        date: str,
    ) -> dict[str, str]:
        return {
            "name": name,
            "category": category,
            "date": date,
        }


class TripPublicHolidayReadTools(BasePublicHolidayTools):
    def _get_supported_skill(self) -> str:
        return "trip.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_trip_public_holidays,
        ]

    def get_trip_public_holidays(
        self, trip_id: str
    ) -> list[dict[str, Any]] | dict[str, Any]:
        """Retrieves public holidays associated with a specific trip.
        Use this when the user asks which public holidays occur during a trip. Resolve the trip ID first using trip tools. Never ask the user for a UUID.

        Args:
            trip_id: The unique identifier of the trip whose public holidays should be retrieved.

        Returns:
            A list of public holidays, each containing:
            - name (string): Holiday name.
            - category (string): Category associated with the holiday.
            - date (string): Holiday date in DD.MM.YYYY format (e.g. `"15.11.2026"`).
        """
        trip = self.core_client.get_trip(trip_id=trip_id)
        if self._is_error_response(trip):
            return trip

        return [
            self._format_holiday(
                name=holiday.get("name"),
                category=holiday.get("category"),
                date=holiday.get("date"),
            )
            for holiday in trip.get("publicHolidays", [])
        ]


class CategoryPublicHolidayReadTools(BasePublicHolidayTools):
    def _get_supported_skill(self) -> str:
        return "category.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_category_public_holidays,
        ]

    def get_category_public_holidays(
        self, category_id: str, year: int | None
    ) -> list[dict[str, Any]] | dict[str, Any]:
        """Retrieves public holidays from a category's configured calendar for one year.
        Use this when the user asks about public holidays in a category or administrative region. Resolve the category ID first using category tools. Never ask the user for a UUID.

        Args:
            category_id: The unique identifier of the category whose public holidays should be retrieved.
            year: Calendar year to retrieve (e.g. 2026). Defaults to the current year.

        Returns:
            A chronologically ordered list of public holidays, each containing:
            - name (string): Holiday name.
            - category (string): Category name associated with the calendar.
            - date (string): Holiday date in DD.MM.YYYY format (e.g. `"24.12.2026"`).
        """
        category = self.core_client.get_category(category_id=category_id)
        if self._is_error_response(category):
            return category

        calendar_url = (category.get("metadata") or {}).get("publicHolidaysCalendar")
        if not calendar_url:
            return {
                "error": 400,
                "message": "The category has no public holidays calendar configured.",
            }

        try:
            calendar_data = self._download_calendar(calendar_url=calendar_url)
            parsed_calendar = Calendar.from_ical(calendar_data)
            if parsed_calendar.name != "VCALENDAR":
                raise RuntimeError("The configured URL did not return an ICS calendar")

            return self._extract_holidays(
                calendar=parsed_calendar,
                year=year if year is not None else datetime.now().year,
                category=category.get("name"),
            )
        except (requests.RequestException, RuntimeError, TypeError) as exc:
            return {
                "error": 400,
                "message": f"Parsing an ICS calendar for the category failed. {exc}.",
            }

    def _download_calendar(self, calendar_url: str) -> bytes:
        response = requests.get(url=calendar_url, timeout=REQUEST_TIMEOUT)
        response.raise_for_status()

        return response.content

    def _extract_holidays(
        self, calendar: Calendar, year: int, category: str
    ) -> list[dict[str, Any]]:
        holidays = []
        for event in calendar.walk("VEVENT"):
            holidays.extend(
                self._extract_event_occurrences(
                    event=event,
                    year=year,
                    category=category,
                )
            )

        return sorted(
            holidays,
            key=lambda holiday: datetime.strptime(
                date_string=holiday["date"],
                format="%d.%m.%Y",
            ),
        )

    def _extract_event_occurrences(
        self, event: Any, year: int, category: str
    ) -> list[dict[str, Any]]:
        if event.get("dtstart") is None:
            return []

        event_start = event.decoded("dtstart")
        all_day = isinstance(event_start, date) and not isinstance(
            event_start, datetime
        )
        recurrence_start = (
            datetime.combine(date=event_start, time=time.min)
            if all_day
            else event_start
        )

        recurrence_dates = rruleset()
        recurrence_dates.rdate(recurrence_start)
        if event.get("rrule") is not None:
            recurrence_dates.rrule(
                rrulestr(
                    s=event.get("rrule").to_ical().decode("utf-8"),
                    dtstart=recurrence_start,
                )
            )

        for recurrence_date in event.get("rdate", []):
            for value in recurrence_date.dts:
                recurrence_dates.rdate(self._as_datetime(value.dt))

        for exclusion_date in event.get("exdate", []):
            for value in exclusion_date.dts:
                recurrence_dates.exdate(self._as_datetime(value.dt))

        start_of_year = datetime(
            year=year,
            month=1,
            day=1,
            tzinfo=recurrence_start.tzinfo,
        )

        start_of_next_year = datetime(
            year=year + 1,
            month=1,
            day=1,
            tzinfo=recurrence_start.tzinfo,
        )

        occurrences = recurrence_dates.between(
            after=start_of_year,
            before=start_of_next_year - timedelta(microseconds=1),
            inc=True,
        )

        return [
            self._format_holiday(
                event=event,
                occurrence=occurrence,
                category=category,
            )
            for occurrence in occurrences
        ]

    def _as_datetime(self, value: date | datetime) -> datetime:
        if isinstance(value, datetime):
            return value

        return datetime.combine(date=value, time=time.min)

    def _format_holiday(
        self,
        event: Any,
        occurrence: datetime,
        category: str,
    ) -> dict[str, Any]:
        user_tz = self._get_user_timezone()
        if occurrence.tzinfo is None:
            occurrence = user_tz.localize(occurrence)

        return self._format_holiday(
            name=unescape(event.get("summary", "")),
            category=category,
            date=self._epoch_to_date(
                epoch=int(occurrence.timestamp()),
                user_tz=user_tz,
            ),
        )
