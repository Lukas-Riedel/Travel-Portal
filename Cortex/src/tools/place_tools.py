from abc import ABC
from typing import Any
from zoneinfo import ZoneInfo

from src.tools.base_tools import BaseCoreTools


class BasePlaceTools(BaseCoreTools, ABC):
    def _extract_place(self, place: dict[str, Any]) -> dict[str, Any] | None:
        place_tz = ZoneInfo(place.get("timezone") or "UTC")
        dates = [d for raw in place.get("dates", []) if (d := self._extract_date(raw, place_tz)) is not None]
        if place.get("dates") and not dates:
            return None

        return {
            "id": place.get("id"),
            "name": place.get("name"),
            "country": place.get("country"),
            "timezone": place.get("timezone"),
            "notes": [note.get("content") for note in place.get("notes", [])],
            "dates": dates,
        }

    def _extract_weather(self, weather: dict[str, Any] | None) -> dict[str, Any] | None:
        if not weather or weather.get("clouds") is None:
            return None

        clouds = weather["clouds"]
        precipitation = weather.get("precipitation") or {}

        return {
            "temperature": weather.get("temperature"),
            "precipitation": {
                "total": precipitation.get("total"),
                "probability": precipitation.get("probability"),
            },
            "clouds": {
                "total": clouds.get("total"),
                "low": clouds.get("low"),
                "medium": clouds.get("medium"),
                "high": clouds.get("high"),
            },
        }

    def _extract_date(self, date: dict[str, Any], place_tz: ZoneInfo) -> dict[str, Any] | None:
        start_epoch = date.get("start")
        if start_epoch is not None and self._get_maximum_timestamp() < start_epoch:
            return None

        end_epoch = date.get("end")
        trip = date.get("trip")

        return {
            "date": self._epoch_to_date(start_epoch, place_tz),
            "start_time": self._epoch_to_time(start_epoch, place_tz),
            "end_time": self._epoch_to_time(end_epoch, place_tz),
            "trip_name": f"{trip.get('name')} {trip.get('year')}" if trip else None,
            "weather_forecast": self._extract_weather(date.get("weather")),
        }

    def get_place_excerpt(self, place_id: str) -> str | None | dict[str, Any]:
        """Retrieves the descriptive excerpt (AI-generated summary paragraph) for a specific place.
        Use this when the user asks for a description or summary of a place . for example
        "describe Prague", "tell me about Angkor Wat", or "give me an overview of the Eiffel Tower".
        Resolve the place ID first using get_place_by_name if only a name is given.

        Args:
            place_id: The unique identifier of the place. Resolve via get_place_by_name . never invent a UUID.

        Returns:
            Null if the place has no excerpt, or the excerpt string.
        """
        place = self.core_client.get_place(place_id)
        if isinstance(place, dict) and place.get("message"):
            return place

        return place.get("excerpt")

class PlaceReadTools(BasePlaceTools):
    def _get_supported_skill(self) -> str:
        return "place.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_places,
            self.get_candidate_places,
            self.get_place_by_name,
            self.get_place_dates,
            self.get_place_excerpt,
        ]

    def get_places(
        self,
        trip_id: str | None,
        label_id: str | None,
        category_id: str | None,
        year: int | None,
        min_start: int | None,
        max_end: int | None,
        sort: str | None,
    ) -> list[dict[str, Any]] | dict[str, Any]:
        """Retrieves a list of regular (visited) places from Travel Portal with optional filters.
        Use this when the user wants to browse, list, or search visited places . for example
        "show me all places I visited in Japan", or "list places from my Egypt trip".

        Args:
            trip_id: Filter by trip. Resolve the trip ID first using trip tools . never ask the user for a UUID.
            label_id: Filter by label identifier. Resolve the label ID first using label tools . never ask the user for a UUID.
            category_id: Filter by category identifier. Resolve the category ID first using category tools . never ask the user for a UUID.
            year: Filter to a specific calendar year (e.g. 2024).
            min_start: Filter to places visited on or after this Unix epoch timestamp.
            max_end: Filter to places visited on or before this Unix epoch timestamp.
            sort: Sorting strategy for the results.
                Available values:
                - 'oldest': Sort by first visit date ascending (earliest first).
                - '-oldest': Sort by first visit date descending (latest first).

        Returns:
            A list of place objects, where each object contains:
            - id (string): Unique identifier of the place. Use it when looking up dates, excerpts, and others.
            - name (string): Name of the place.
            - country (string | null): Country the place is located in.
            - timezone (string): IANA timezone identifier of the place.
            - notes (list of strings): Notes relevant to the place.
            - dates (list of objects): Visit dates, each containing:
                - date (string): Date of the visit in YYYY-MM-DD format.
                - start_time (string): Start time of the visit in HH:MM format (in the place's local timezone).
                - end_time (string): End time of the visit in HH:MM format (in the place's local timezone).
                - trip_name (string | null): Name and year of the associated trip (e.g. `"Egypt 2024"`), if any.
                - weather_forecast (object | null): Weather forecast, available only for dates in the near future. Contains temperature (°C), precipitation (total mm, probability %), and clouds (total, low, medium, high %).
        """
        places = self.core_client.get_places(
            place_type="regular",
            trip_id=trip_id,
            label_id=label_id,
            category_id=category_id,
            year=year,
            min_start=min_start,
            max_end=max_end,
            include="notes,dates",
            sort=sort,
        )
        if isinstance(places, dict) and places.get("message"):
            return places

        return [p for place in places if (p := self._extract_place(place)) is not None]

    def get_candidate_places(
        self,
        trip_id: str | None,
        label_id: str | None,
        category_id: str | None,
    ) -> list[dict[str, Any]] | dict[str, Any]:
        """Retrieves a list of candidate (wishlist / unplanned) places from Travel Portal.
        Use this when the user asks about places they want to visit, unplanned destinations,
        or their travel wishlist . for example "show me my candidate places" or "what places am I considering?".

        Args:
            trip_id: Filter by trip identifier. Resolve the trip ID first using trip tools . never ask the user for a UUID.
            label_id: Filter by label identifier. Resolve the label ID first using label tools . never ask the user for a UUID.
            category_id: Filter by category identifier. Resolve the category ID first using category tools . never ask the user for a UUID.

        Returns:
            A list of place objects, where each object contains:
            - id (string): Unique identifier of the place. Use it when looking up dates, excerpts, and others.
            - name (string): Name of the place.
            - country (string | null): Country the place is located in.
            - timezone (string): IANA timezone identifier of the place.
            - notes (list of strings): Notes relevant to the place.
            - dates (list of objects): Visit dates, each containing:
                - date (string): Date of the visit in YYYY-MM-DD format.
                - start_time (string): Start time of the visit in HH:MM format (in the place's local timezone).
                - end_time (string): End time of the visit in HH:MM format (in the place's local timezone).
                - trip_name (string | null): Name and year of the associated trip (e.g. `"Egypt 2024"`), if any.
                - weather_forecast (object | null): Weather forecast, available only for dates in the near future. Contains temperature (°C), precipitation (total mm, probability %), and clouds (total, low, medium, high %).
        """
        places = self.core_client.get_places(
            place_type="candidate",
            trip_id=trip_id,
            label_id=label_id,
            category_id=category_id,
            include="notes,dates",
        )
        if isinstance(places, dict) and places.get("message"):
            return places

        return [p for place in places if (p := self._extract_place(place)) is not None]

    def get_place_by_name(self, query: str) -> dict[str, Any] | None:
        """Finds a specific place by searching the Travel Portal full-text search index for places
        matching the given query, then fetches the full place object for the best match.
        Use this when you need a place ID from a user-provided name, or when the user refers to a
        specific place . for example "tell me about Prague", "find Angkor Wat".
        If no match is found, or if the result is unsound, ask the user for clarification.

        Args:
            query: The search term to match against place names. Use the place name or a key noun,
                e.g. "Prague", "Angkor Wat", "Eiffel Tower". Do not pass a full sentence.

        Returns:
            Null if no such place exists, or a place object containing:
            - id (string): Unique identifier of the place. Use it when looking up dates, excerpts, and others.
            - name (string): Name of the place.
            - country (string | null): Country the place is located in.
            - timezone (string): IANA timezone identifier of the place.
            - notes (list of strings): Notes relevant to the place.
            - dates (list of objects): Visit dates, each containing:
                - date (string): Date of the visit in YYYY-MM-DD format.
                - start_time (string): Start time of the visit in HH:MM format (in the place's local timezone).
                - end_time (string): End time of the visit in HH:MM format (in the place's local timezone).
                - trip_name (string | null): Name and year of the associated trip (e.g. `"Egypt 2024"`), if any.
                - weather_forecast (object | null): Weather forecast, available only for dates in the near future. Contains temperature (°C), precipitation (total mm, probability %), and clouds (total, low, medium, high %).
        """
        search_results = self.core_client.search(query=query, include="place", limit=1)
        if isinstance(search_results, dict) and search_results.get("message"):
            return search_results

        place_result = next((r for r in search_results if r.get("type") == "place"), None)
        if place_result is None:
            return None

        place_id = (place_result.get("entity") or {}).get("id")
        if not place_id:
            return None

        place = self.core_client.get_place(place_id)
        if isinstance(place, dict) and place.get("message"):
            return place

        return self._extract_place(place)

    def get_place_dates(self, place_id: str) -> list[dict[str, Any]] | dict[str, Any] | None:
        """Retrieves all visit dates for a specific place, including weather forecast for future dates.
        Use this when the user asks when they visited a place, how many times they have been there,
        or what the weather will be like on an upcoming visit . for example "when did I visit Kyoto?",
        "how many times have I been to Paris?", or "what's the weather like at my next stop?".
        Resolve the place ID first using get_place_by_name if only a name is given.

        Args:
            place_id: The unique identifier of the place. Resolve via get_place_by_name . never invent a UUID.

        Returns:
            Null if the place does not exist, or a list of date objects each containing:
            - date (string): Date of the visit in YYYY-MM-DD format.
            - start_time (string): Start time of the visit in HH:MM format (in the place's local timezone).
            - end_time (string): End time of the visit in HH:MM format (in the place's local timezone).
            - trip_name (string | null): Name and year of the associated trip (e.g. `"Egypt 2024"`), if any.
            - weather_forecast (object | null): Weather forecast, available only for dates in the near future. Contains temperature (°C), precipitation (total mm, probability %), and clouds (total, low, medium, high %).
        """
        place = self.core_client.get_place(place_id)
        if isinstance(place, dict) and place.get("message"):
            return place

        place_tz = ZoneInfo(place.get("timezone") or "UTC")
        return [d for raw in place.get("dates", []) if (d := self._extract_date(raw, place_tz)) is not None]

class PlaceEditTools(BasePlaceTools):
    def _get_supported_skill(self) -> str:
        return "place.edit"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_place_excerpt,
            self.create_candidate_place,
            self.update_place_excerpt,
        ]

    def create_candidate_place(self, name: str, address: str) -> dict[str, Any] | dict[str, Any] | None:
        """Creates a new candidate (wishlist) place in Travel Portal.
        Use this when the user wants to add a place they wish to visit in the future . for example
        "add Petra to my wishlist", "create a candidate place for Machu Picchu".
        Always ask the user for both the place name and address before calling this tool.

        Args:
            name: The name of the place (e.g. "Petra", "Machu Picchu").
            address: The full address of the place as provided by the user
                (e.g. "Petra, Ma'an Governorate, Jordan"). Do not geocode or modify the address.

        Returns:
            The created place object containing:
            - id (string): Unique identifier of the place. Use it when looking up dates, excerpts, and others.
            - name (string): Name of the place.
            - country (string | null): Country the place is located in.
            - timezone (string): IANA timezone identifier of the place.
            - notes (list of strings): Notes relevant to the place.
            - dates (list of objects): Visit dates, each containing:
                - date (string): Date of the visit in YYYY-MM-DD format.
                - start_time (string): Start time of the visit in HH:MM format (in the place's local timezone).
                - end_time (string): End time of the visit in HH:MM format (in the place's local timezone).
                - trip_name (string | null): Name and year of the associated trip (e.g. `"Egypt 2024"`), if any.
                - weather_forecast (object | null): Weather forecast, available only for dates in the near future. Contains temperature (°C), precipitation (total mm, probability %), and clouds (total, low, medium, high %).
        """
        place = self.core_client.create_place(name=name, address=address, place_type="candidate")
        if isinstance(place, dict) and place.get("message"):
            return place

        return self._extract_place(place)

    def update_place_excerpt(self, place_id: str, excerpt: str) -> dict[str, Any] | None:
        """Updates the descriptive excerpt of a place in Travel Portal.
        Use this when the user asks to change or rewrite a place description.
        The expected flow is: call get_place_excerpt first to show the current text, let the user
        specify what to change, then call this tool with the fully rewritten excerpt.

        Args:
            place_id: The unique identifier of the place. Resolve via get_place_by_name . never invent a UUID.
            excerpt: The complete new excerpt text (not a diff or partial update . always pass the full text).

        Returns:
            The updated place object containing:
            - id (string): Unique identifier of the place. Use it when looking up dates, excerpts, and others.
            - name (string): Name of the place.
            - country (string | null): Country the place is located in.
            - timezone (string): IANA timezone identifier of the place.
            - notes (list of strings): Notes relevant to the place.
            - dates (list of objects): Visit dates, each containing:
                - date (string): Date of the visit in YYYY-MM-DD format.
                - start_time (string): Start time of the visit in HH:MM format (in the place's local timezone).
                - end_time (string): End time of the visit in HH:MM format (in the place's local timezone).
                - trip_name (string | null): Name and year of the associated trip (e.g. `"Egypt 2024"`), if any.
                - weather_forecast (object | null): Weather forecast, available only for dates in the near future. Contains temperature (°C), precipitation (total mm, probability %), and clouds (total, low, medium, high %).
        """
        place = self.core_client.update_place_excerpt(place_id=place_id, excerpt=excerpt)
        if isinstance(place, dict) and place.get("message"):
            return place

        return self._extract_place(place)