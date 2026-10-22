import time
from datetime import datetime, tzinfo
from typing import Any

from src.tools.base_tools import BaseCoreTools


class TripReadTools(BaseCoreTools):
    def _get_supported_skill(self) -> str:
        return "trip.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_upcoming_trip,
            self.get_current_trip,
            self.get_trip_by_name,
            self.get_regular_trips,
            self.get_candidate_trips,
        ]

    def get_upcoming_trip(self) -> dict[str, Any] | None:
        """Retrieves the next trip that starts in the future - the one with the earliest start date that has not yet begun.
        Call this when the user asks about "my next trip", "upcoming trip", or "when am I travelling next".
        Returns null if there are no future trips scheduled.

        Returns:
            Null if no upcoming trip exists, or the nearest future trip object containing:
            - id (string): Unique identifier of the trip. Use it when looking up stays, flights, public holidays and others.
            - name (string): Title/name of the trip.
            - year (integer): Year of the trip.
            - start (string): Start date in DD.MM.YYYY format (e.g. `"27.12.2025"`).
            - end (string): End date in DD.MM.YYYY format (e.g. `"31.12.2025"`).
            - days (integer): Number of calendar days the trip spans (inclusive).
            - countries (list of strings): Countries visited on thh trip (excluding layovers).
        """
        trips = self.core_client.get_trips(
            trip_type="regular",
            sort="oldest",
        )
        if self._is_error_response(trips):
            return trips

        current_timestamp = int(time.time())
        for trip in trips:
            if trip.get("start") > current_timestamp:
                if not self._is_trip_visible(trip):
                    return None

                system_tz = self._get_system_timezone()
                return self._extract_trip(trip=trip, system_tz=system_tz)

        return None

    def get_current_trip(self) -> dict[str, Any] | None:
        """Retrieves the trip that is currently in progress - i.e. the trip whose start date is in the past and end date is in the future relative to right now.
        Call this when the user asks about "my current trip", "where am I right now", or "what trip am I on".
        Returns null if no trip is active at this moment; in that case consider calling  get_upcoming_trip to find what is coming next.

        Returns:
            Null if no current trip exists, or the current trip object containing:
            - id (string): Unique identifier of the trip. Use it when looking up stays, flights, public holidays and others.
            - name (string): Title/name of the trip.
            - year (integer): Year of the trip.
            - start (string): Start date in DD.MM.YYYY format (e.g. `"27.12.2025"`).
            - end (string): End date in DD.MM.YYYY format (e.g. `"31.12.2025"`).
            - days (integer): Number of calendar days the trip spans (inclusive).
            - countries (list of strings): Countries visited on thh trip (excluding layovers).
        """
        trips = self.core_client.get_trips(
            trip_type="regular",
            sort="oldest",
        )
        if self._is_error_response(trips):
            return trips

        current_timestamp = int(time.time())
        for trip in trips:
            if (
                trip.get("start") < current_timestamp
                and trip.get("end") > current_timestamp
            ):
                if not self._is_trip_visible(trip):
                    return None

                system_tz = self._get_system_timezone()
                return self._extract_trip(trip=trip, system_tz=system_tz)

        return None

    def get_trip_by_name(self, query: str) -> dict[str, Any] | None:
        """Finds a specific trip by searching the Travel Portal full-text search index for trips matching the given query, then fetches the full trip object for the best match.
        Use this when the user refers to a trip by destination or title - for example "my Egypt trip", "when did I go to Vietnam", or "the Sri Lanka journey".
        The search is powered by OpenSearch and handles partial words, fuzzy matches, and multi-word queries well.
        If no match is found, consider falling back to get_upcoming_trip or ask the user to clarify.

        Args:
            query: The search term to match against trip names and country names. Use a destination or keyword from the trip title, e.g. "Sri Lanka", "Egypt", "Grand Canyon", "Las Vegas". Do not pass a full sentence - use the key noun only.

        Returns:
            Null if no such trip exists, or the trip object containing:
            - id (string): Unique identifier of the trip. Use it when looking up stays, flights, public holidays and others.
            - name (string): Title/name of the trip.
            - year (integer): Year of the trip.
            - start (string): Start date in DD.MM.YYYY format (e.g. `"27.12.2025"`).
            - end (string): End date in DD.MM.YYYY format (e.g. `"31.12.2025"`).
            - days (integer): Number of calendar days the trip spans (inclusive).
            - countries (list of strings): Countries visited on thh trip (excluding layovers).
        """
        search_results = self.core_client.search(query=query, include="trip", limit=1)
        if self._is_error_response(search_results):
            return search_results

        trip_result = next((r for r in search_results if r.get("type") == "trip"), None)
        if trip_result is None:
            return None

        trip_id = (trip_result.get("entity") or {}).get("id")
        if not trip_id:
            return None

        trip = self.core_client.get_trip(trip_id)
        if self._is_error_response(trip):
            return trip

        if not self._is_trip_visible(trip):
            return None

        system_tz = self._get_system_timezone()
        return self._extract_trip(trip=trip, system_tz=system_tz)

    def get_regular_trips(
        self, year: int | None, sort: str | None
    ) -> list[dict[str, Any]]:
        """Retrieves a list of trips from Travel Portal.
        Use this tool when you need to browse, list, or inspect multiple trips at once - for example to answer questions like "how many trips did I take in 2024?" or "show me all my trips".
        For a single specific trip use get_current_trip, get_upcoming_trip, or get_trip_by_name instead.

        Args:
            year: Filter trips to a specific calendar year (e.g. 2025). Omit or pass None to retrieve trips across all years.
            sort: Sorting strategy for the trips.
                Available values:
                - 'oldest': Sort by start date ascending (earliest trips first).
                - '-oldest': Sort by start date descending (latest trips first).
                - 'longest': Sort by duration descending (longest trips first).
                - '-longest': Sort by duration ascending (shortest trips first).

        Returns:
            A list of trip objects, where each object contains:
            - id (string): Unique identifier of the trip. Use it when looking up stays, flights, public holidays and others.
            - name (string): Title/name of the trip.
            - year (integer): Year of the trip.
            - start (string): Start date in DD.MM.YYYY format (e.g. `"27.12.2025"`).
            - end (string): End date in DD.MM.YYYY format (e.g. `"31.12.2025"`).
            - days (integer): Number of calendar days the trip spans (inclusive).
            - countries (list of strings): Countries visited on thh trip (excluding layovers).
        """
        trips = self.core_client.get_trips(
            trip_type="regular",
            year=year,
            sort=sort,
        )
        if self._is_error_response(trips):
            return trips

        system_tz = self._get_system_timezone()
        return [
            self._extract_trip(trip=trip, system_tz=system_tz)
            for trip in trips
            if self._is_trip_visible(trip)
        ]

    def get_candidate_trips(self) -> list[dict[str, Any]]:
        """Retrieves a list of candidate trips from Travel Portal.
        Use this tool when you need to browse, list, or inspect multiple candidate trips at once - for example to answer questions like "are there any unplanned trips?" or "show me all my candidate trips".
        For a single specific trip use get_current_trip, get_upcoming_trip, or get_trip_by_name instead.

        Returns:
            A list of trip objects, where each object contains:
            - id (string): Unique identifier of the trip. Use it when looking up stays, flights, public holidays and others.
            - name (string): Title/name of the trip.
            - year (integer): Year of the trip.
            - start (string): Start date in DD.MM.YYYY format (e.g. `"27.12.2025"`).
            - end (string): End date in DD.MM.YYYY format (e.g. `"31.12.2025"`).
            - days (integer): Number of calendar days the trip spans (inclusive).
            - countries (list of strings): Countries visited on thh trip (excluding layovers).
        """
        trips = self.core_client.get_trips(
            trip_type="candidate",
        )
        if self._is_error_response(trips):
            return trips

        system_tz = self._get_system_timezone()
        return [
            self._extract_trip(trip=trip, system_tz=system_tz)
            for trip in trips
            if self._is_trip_visible(trip)
        ]

    def _extract_trip(
        self,
        trip: dict[str, Any],
        system_tz: tzinfo,
    ) -> dict[str, Any]:
        extracted_trip = {
            "id": trip.get("id"),
            "name": trip.get("name"),
            "countries": trip.get("countries", []),
        }

        if trip.get("year") is not None:
            start_epoch = trip.get("start")
            end_epoch = trip.get("end") - 1

            start_date = datetime.fromtimestamp(start_epoch, tz=system_tz).date()
            end_date = datetime.fromtimestamp(end_epoch, tz=system_tz).date()

            extracted_trip["year"] = trip.get("year")
            extracted_trip["days"] = (end_date - start_date).days + 1
            extracted_trip["start"] = self._epoch_to_date(
                epoch=start_epoch, user_tz=system_tz
            )
            extracted_trip["end"] = self._epoch_to_date(
                epoch=end_epoch, user_tz=system_tz
            )

        return extracted_trip
