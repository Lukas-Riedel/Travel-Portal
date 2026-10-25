from abc import ABC
from datetime import date, datetime, time, timedelta
from html import unescape
from typing import Any, Final, Literal

import requests
from dateutil.rrule import rruleset, rrulestr
from icalendar import Calendar

from src.errors.general_tool_error import GeneralToolError
from src.tools.base_tools import BaseCoreTools

REQUEST_TIMEOUT: Final[tuple[Literal[5], Literal[15]]] = (5, 15)


class BaseCategoryTools(BaseCoreTools, ABC):
    def _extract_category(self, category: dict[str, Any]) -> dict[str, Any]:
        return {
            "id": category.get("id"),
            "name": category.get("name"),
            "type": category.get("category"),
        }


class CategoryReadTools(BaseCategoryTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to list and find categories in Travel Portal. Activate and apply this skill when the user asks about categories, their types, or a category by name (e.g. "show me all island categories", "list administrative regions in Czechia", "find the category Europe").'

    def _get_supported_skill(self) -> str:
        return "category.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_categories,
            self.get_category_by_name,
            self.get_category_public_holidays,
        ]

    def get_categories(
        self,
        category_type: str | None,
        country: str | None,
    ) -> list[dict[str, Any]]:
        """Retrieves categories, optionally filtered by type or country.
        Use this when the user asks about categories - for example "show me all island categories" or "list administrative regions in Czechia".

        Args:
            category_type: Filter the results to one category type: continent, country, administrative, ocean, sea, bay, island, or region. Pass None for all types.
            country: Filter administrative categories to a country (e.g. "Czechia"). Pass None for categories without a country filter.

        Returns:
            A list of category objects, where each object contains:
            - id (string): Unique category identifier.
            - name (string): Category name (e.g. `"Europe"`).
            - type (string): Category type: continent, country, administrative, ocean, sea, bay, island, or region (e.g. `"continent"`).
        """
        categories = self.core_client.get_categories(
            country=country,
            categories="administrative" if country is not None else category_type,
        )
        self._verify_error_response(categories)

        return [self._extract_category(category) for category in categories]

    def get_category_by_name(self, query: str) -> dict[str, Any] | None:
        """Finds a category by searching the Travel Portal full-text search index.
        Use this when the user refers to a category by name - for example "find the category Europe" or "look up the United Kingdom category".

        Args:
            query: The category name or key words to search for (e.g. "Europe" or "United Kingdom"). Do not pass a full sentence.

        Returns:
            Null if no matching category exists, or a category object containing:
            - id (string): Unique category identifier.
            - name (string): Category name (e.g. `"Europe"`).
            - type (string): Category type: continent, country, administrative, ocean, sea, bay, island, or region (e.g. `"continent"`).
        """
        search_results = self.core_client.search(
            query=query,
            include="category",
            limit=1,
        )
        self._verify_error_response(search_results)

        category_result = next(
            (result for result in search_results if result.get("type") == "category"),
            None,
        )
        if category_result is None:
            return None

        category_id = (category_result.get("entity") or {}).get("id")
        if not category_id:
            return None

        category = self.core_client.get_category(category_id)
        self._verify_error_response(category)

        return self._extract_topic(
            topic_ids=[category_id],
            data=self._extract_category(category),
        )

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
        self._verify_error_response(category)

        calendar_url = (category.get("metadata") or {}).get("publicHolidaysCalendar")
        if not calendar_url:
            raise RuntimeError(
                "The category has no public holidays calendar configured"
            )

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
            raise GeneralToolError(
                f"Parsing an ICS calendar for the category failed. {exc}."
            ) from exc

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
            key=lambda holiday: datetime.strptime(holiday["date"], "%d.%m.%Y"),
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

        return {
            "name": unescape(event.get("summary", "")),
            "category": category,
            "date": self._epoch_to_date(
                epoch=int(occurrence.timestamp()),
                user_tz=user_tz,
            ),
        }


class PlaceCategoryReadTools(BaseCategoryTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to query categories associated with places in Travel Portal. Activate and apply this skill when the user asks which categories or regions a specific place belongs to (e.g. "which regions does Prague belong to?", "what categories are associated with Angkor Wat?").'

    def _get_supported_skill(self) -> str:
        return "place.category.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_categories_for_place,
        ]

    def get_categories_for_place(self, place_id: str) -> list[dict[str, Any]]:
        """Retrieves the categories associated with a specific place.
        Use this when the user asks which categories or regions a place belongs to. Resolve the place first using place tools. Never ask the user for a UUID.

        Args:
            place_id: The unique identifier of the place, resolved using place tools.

        Returns:
            A list of category objects, where each object contains:
            - id (string): Unique category identifier.
            - name (string): Category name (e.g. `"Europe"`).
            - type (string): Category type: continent, country, administrative, ocean, sea, bay, island, or region (e.g. `"continent"`).
        """
        place = self.core_client.get_place(place_id)
        self._verify_error_response(place)

        categories = []
        for category_identifier in place.get("categories", []):
            category = self.core_client.get_category(category_identifier.get("id"))
            self._verify_error_response(category)

            categories.append(self._extract_category(category))

        return categories
