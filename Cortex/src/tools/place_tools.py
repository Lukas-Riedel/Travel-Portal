import time
from abc import ABC
from typing import Any
from zoneinfo import ZoneInfo

from src.tools.base_tools import BaseCoreTools


class BasePlaceTools(BaseCoreTools, ABC):
    def _filter_visible_dates(
        self, dates: list[dict[str, Any]]
    ) -> list[dict[str, Any]]:
        maximum_timestamp = self._get_maximum_timestamp()
        return [
            d
            for d in dates
            if d.get("start") is None or d["start"] <= maximum_timestamp
        ]

    def _with_visible_dates(self, place: dict[str, Any]) -> dict[str, Any]:
        return {**place, "dates": self._filter_visible_dates(place.get("dates", []))}

    def _filter_visible_places(
        self, places: list[dict[str, Any]]
    ) -> list[dict[str, Any]]:
        return [
            visible
            for place in places
            if (visible := self._with_visible_dates(place))["dates"]
            or not place.get("dates")
        ]

    def _extract_place(self, place: dict[str, Any]) -> dict[str, Any]:
        place_tz = ZoneInfo(place.get("timezone") or "UTC")
        dates = [
            self._extract_date(date=raw, place_tz=place_tz)
            for raw in place.get("dates", [])
        ]

        return {
            "id": place.get("id"),
            "name": place.get("name"),
            "country": place.get("country"),
            "timezone": place.get("timezone"),
            "dates": dates,
        }

    def _aggregate_weather(
        self, weather_list: list[dict[str, Any]]
    ) -> dict[str, Any] | None:
        if not weather_list:
            return None

        temp_sum = 0.0
        temp_count = 0
        precip_probability = None
        precip_total = 0.0
        clouds_total_sum = clouds_low_sum = clouds_medium_sum = clouds_high_sum = 0.0
        clouds_count = 0

        for w in weather_list:
            if (t := w.get("temperature")) is not None:
                temp_sum += t
                temp_count += 1

            precip = w.get("precipitation") or {}
            if (prob := precip.get("probability")) is not None:
                precip_probability = (
                    prob
                    if precip_probability is None
                    else max(precip_probability, prob)
                )
            if (total := precip.get("total")) is not None:
                precip_total += total

            clouds = w.get("clouds")
            if clouds is not None:
                if (ct := clouds.get("total")) is not None:
                    clouds_total_sum += ct
                    clouds_count += 1
                if (cl := clouds.get("low")) is not None:
                    clouds_low_sum += cl
                if (cm := clouds.get("medium")) is not None:
                    clouds_medium_sum += cm
                if (ch := clouds.get("high")) is not None:
                    clouds_high_sum += ch

        if clouds_count == 0:
            return None

        return {
            "temperature": temp_sum / temp_count if temp_count > 0 else None,
            "precipitation": {
                "total": precip_total,
                "probability": precip_probability,
            },
            "clouds": {
                "total": round(clouds_total_sum / clouds_count),
                "low": round(clouds_low_sum / clouds_count),
                "medium": round(clouds_medium_sum / clouds_count),
                "high": round(clouds_high_sum / clouds_count),
            },
        }

    def _extract_date(self, date: dict[str, Any], place_tz: ZoneInfo) -> dict[str, Any]:
        start_epoch = date.get("start")
        end_epoch = date.get("end")
        trip = date.get("trip")

        return {
            "date": self._epoch_to_date(epoch=start_epoch, user_tz=place_tz),
            "start_time": self._epoch_to_time(epoch=start_epoch, user_tz=place_tz),
            "end_time": self._epoch_to_time(epoch=end_epoch, user_tz=place_tz),
            "trip_name": f"{trip.get('name')} {trip.get('year')}" if trip else None,
            "weather_forecast": self._aggregate_weather(date.get("weather") or []),
        }

    def get_place_excerpt(self, place_id: str) -> str | None:
        """Retrieves the descriptive excerpt (AI-generated summary paragraph) for a specific place.
        Use this when the user asks for a description or summary of a place - for example
        "describe Prague", "tell me about Angkor Wat", or "give me an overview of the Eiffel Tower".
        Resolve the place ID first using get_place_by_name if only a name is given.

        Args:
            place_id: The unique identifier of the place. Resolve via get_place_by_name.

        Returns:
            The excerpt string.
        """
        place = self.core_client.get_place(place_id)
        if self._is_error_response(place):
            return place

        return place.get("excerpt")


class PlaceReadTools(BasePlaceTools):
    def _get_supported_skill(self) -> str:
        return "place.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_places,
            self.get_recently_visited_places,
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
    ) -> list[dict[str, Any]]:
        """Retrieves a list of regular (visited) places from Travel Portal with optional filters.
        Use this when the user wants to browse, list, or search visited places - for example
        "show me all places I visited in Japan", or "list places from my Egypt trip".

        Args:
            trip_id: Filter by trip. Resolve the trip ID first using trip tools. Never ask the user for a UUID.
            label_id: Filter by label. Resolve the label ID first using label tools. Never ask the user for a UUID.
            category_id: Filter by category. Resolve the category ID first using category tools. Never ask the user for a UUID.
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
            - country (string): Country the place is located in.
            - timezone (string): IANA timezone identifier of the place.
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
            include="dates"
            if trip_id is not None
            or label_id is not None
            or category_id is not None
            or year is not None
            else None,
            sort=sort,
        )
        if self._is_error_response(places):
            return places

        return [self._extract_place(p) for p in self._filter_visible_places(places)]

    def get_recently_visited_places(
        self,
        limit: int,
        category_id: str | None,
    ) -> list[dict[str, Any]]:
        """Retrieves the most recently visited regular places from Travel Portal, latest first.
        Use this when the user asks about their latest or last visited places - for example
        "where have I been recently?", "show me the last 5 places I visited".
        Only places that have already been visited are returned (planned future visits are excluded).

        Args:
            limit: The maximum number of places to return (e.g. 5).
            category_id: Filter by category. Resolve the category ID first using category tools. Never ask the user for a UUID.

        Returns:
            A list of place objects sorted by the visit date descending (latest first), where each object contains:
            - id (string): Unique identifier of the place. Use it when looking up dates, excerpts, and others.
            - name (string): Name of the place.
            - country (string): Country the place is located in.
            - timezone (string): IANA timezone identifier of the place.
            - dates (list of objects): Visit dates, each containing:
                - date (string): Date of the visit in YYYY-MM-DD format.
                - start_time (string): Start time of the visit in HH:MM format (in the place's local timezone).
                - end_time (string): End time of the visit in HH:MM format (in the place's local timezone).
                - trip_name (string | null): Name and year of the associated trip (e.g. `"Egypt 2024"`), if any.
        """
        places = self.core_client.get_places(
            place_type="regular",
            category_id=category_id,
            max_end=min(int(time.time()), self._get_maximum_timestamp()),
            include="dates",
            sort="-oldest",
            limit=limit,
        )
        if self._is_error_response(places):
            return places

        return [self._extract_place(p) for p in self._filter_visible_places(places)]

    def get_candidate_places(
        self,
        trip_id: str | None,
        label_id: str | None,
        category_id: str | None,
    ) -> list[dict[str, Any]]:
        """Retrieves a list of candidate (wishlist / unplanned) places from Travel Portal.
        Use this when the user asks about places they want to visit, unplanned destinations,
        or their travel wishlist - for example "show me my candidate places" or "what places am I considering?".

        Args:
            trip_id: Filter by trip. Resolve the trip ID first using trip tools. Never ask the user for a UUID.
            label_id: Filter by label. Resolve the label ID first using label tools. Never ask the user for a UUID.
            category_id: Filter by category. Resolve the category ID first using category tools. Never ask the user for a UUID.

        Returns:
            A list of place objects, where each object contains:
            - id (string): Unique identifier of the place. Use it when looking up dates, excerpts, and others.
            - name (string): Name of the place.
            - country (string): Country the place is located in.
            - timezone (string): IANA timezone identifier of the place.
            - dates (list of objects): Visit dates, each containing:
                - date (string): Date of the visit in YYYY-MM-DD format.
                - start_time (string): Start time of the visit in HH:MM format (in the place's local timezone).
                - end_time (string): End time of the visit in HH:MM format (in the place's local timezone).
                - trip_name (string | null): Name and year of the associated candidate trip (e.g. `"Egypt"`), if any.
        """
        places = self.core_client.get_places(
            place_type="candidate",
            trip_id=trip_id,
            label_id=label_id,
            category_id=category_id,
            include="dates" if trip_id is not None else None,
        )
        if self._is_error_response(places):
            return places

        return [self._extract_place(p) for p in self._filter_visible_places(places)]

    def get_place_by_name(self, query: str) -> dict[str, Any] | None:
        """Finds a specific place by searching the Travel Portal full-text search index for places
        matching the given query, then fetches the full place object for the best match.
        Use this when you need a place ID from a user-provided name, or when the user refers to a
        specific place - for example "tell me about Prague", "find Angkor Wat".
        If no match is found, or if the result is unsound, ask the user for clarification.

        Args:
            query: The search term to match against place names. Use the place name or a key noun,
                e.g. "Prague", "Angkor Wat", "Eiffel Tower". Do not pass a full sentence.

        Returns:
            Null if no such place exists, or a place object containing:
            - id (string): Unique identifier of the place. Use it when looking up dates, excerpts, and others.
            - name (string): Name of the place.
            - country (string): Country the place is located in.
            - timezone (string): IANA timezone identifier of the place.
            - dates (list of objects): Visit dates, each containing:
                - date (string): Date of the visit in YYYY-MM-DD format.
                - start_time (string): Start time of the visit in HH:MM format (in the place's local timezone).
                - end_time (string): End time of the visit in HH:MM format (in the place's local timezone).
                - trip_name (string | null): Name and year of the associated trip (e.g. `"Egypt 2024"`), if any.
                - weather_forecast (object | null): Weather forecast, available only for dates in the near future. Contains temperature (°C), precipitation (total mm, probability %), and clouds (total, low, medium, high %).
        """
        search_results = self.core_client.search(query=query, include="place", limit=1)
        if self._is_error_response(search_results):
            return search_results

        place_result = next(
            (r for r in search_results if r.get("type") == "place"), None
        )
        if place_result is None:
            return None

        place_id = (place_result.get("entity") or {}).get("id")
        if not place_id:
            return None

        place = self.core_client.get_place(place_id)
        if self._is_error_response(place):
            return place

        visible_places = self._filter_visible_places([place])
        if not visible_places:
            return None

        return self._extract_place(visible_places[0])

    def get_place_dates(self, place_id: str) -> list[dict[str, Any]]:
        """Retrieves all visit dates for a specific place, including weather forecast for future dates.
        Use this when the user asks when they visited a place, how many times they have been there,
        or what the weather will be like on an upcoming visit - for example "when did I visit Kyoto?",
        "how many times have I been to Paris?", or "what's the weather like at my next stop?".
        Resolve the place ID first using get_place_by_name if only a name is given.

        Args:
            place_id: The unique identifier of the place. Resolve via get_place_by_name.

        Returns:
            An empty list if the place has no visit dates, or a list of date objects each containing:
            - date (string): Date of the visit in YYYY-MM-DD format.
            - start_time (string): Start time of the visit in HH:MM format (in the place's local timezone).
            - end_time (string): End time of the visit in HH:MM format (in the place's local timezone).
            - trip_name (string | null): Name and year of the associated trip (e.g. `"Egypt 2024"`), if any.
            - weather_forecast (object | null): Weather forecast, available only for dates in the near future. Contains temperature (°C), precipitation (total mm, probability %), and clouds (total, low, medium, high %).
        """
        place = self.core_client.get_place(place_id)
        if self._is_error_response(place):
            return place

        place_tz = ZoneInfo(place.get("timezone") or "UTC")
        return [
            self._extract_date(date=raw, place_tz=place_tz)
            for raw in self._filter_visible_dates(place.get("dates", []))
        ]


class PlaceEditTools(BasePlaceTools):
    def _get_supported_skill(self) -> str:
        return "place.edit"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_place_excerpt,
            self.create_candidate_place,
            self.update_place_excerpt,
        ]

    def create_candidate_place(self, name: str, address: str) -> dict[str, Any]:
        """Creates a new candidate (wishlist) place in Travel Portal.
        Use this when the user wants to add a place they wish to visit in the future - for example
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
            - country (string): Country the place is located in.
            - timezone (string): IANA timezone identifier of the place.
            - dates (list of objects): Visit dates, each containing:
                - date (string): Date of the visit in YYYY-MM-DD format.
                - start_time (string): Start time of the visit in HH:MM format (in the place's local timezone).
                - end_time (string): End time of the visit in HH:MM format (in the place's local timezone).
                - trip_name (string | null): Name and year of the associated trip (e.g. `"Egypt 2024"`), if any.
                - weather_forecast (object | null): Weather forecast, available only for dates in the near future. Contains temperature (°C), precipitation (total mm, probability %), and clouds (total, low, medium, high %).
        """
        place = self.core_client.create_place(
            name=name, address=address, place_type="candidate"
        )
        if self._is_error_response(place):
            return place

        return self._extract_place(self._with_visible_dates(place))

    def update_place_excerpt(self, place_id: str, excerpt: str) -> dict[str, Any]:
        """Updates the descriptive excerpt of a place in Travel Portal.
        Use this when the user asks to change or rewrite a place description.
        The expected flow is: call get_place_excerpt first to show the current text, let the user
        specify what to change, then call this tool with the fully rewritten excerpt.

        Args:
            place_id: The unique identifier of the place. Resolve via get_place_by_name.
            excerpt: The complete new excerpt text (not a diff or partial update . always pass the full text).

        Returns:
            The updated place object containing:
            - id (string): Unique identifier of the place. Use it when looking up dates, excerpts, and others.
            - name (string): Name of the place.
            - country (string): Country the place is located in.
            - timezone (string): IANA timezone identifier of the place.
            - dates (list of objects): Visit dates, each containing:
                - date (string): Date of the visit in YYYY-MM-DD format.
                - start_time (string): Start time of the visit in HH:MM format (in the place's local timezone).
                - end_time (string): End time of the visit in HH:MM format (in the place's local timezone).
                - trip_name (string | null): Name and year of the associated trip (e.g. `"Egypt 2024"`), if any.
                - weather_forecast (object | null): Weather forecast, available only for dates in the near future. Contains temperature (°C), precipitation (total mm, probability %), and clouds (total, low, medium, high %).
        """
        place = self.core_client.update_place(place_id=place_id, excerpt=excerpt)
        if self._is_error_response(place):
            return place

        return self._extract_place(self._with_visible_dates(place))
