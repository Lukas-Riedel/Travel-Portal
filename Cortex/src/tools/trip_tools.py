import time
from datetime import datetime
from typing import Any
from zoneinfo import ZoneInfo

from src.tools.base_tools import CoreApiTools


class TripTools(CoreApiTools):
    def get_tools(self) -> list[Any]:
        return [
            self.get_upcoming_trip,
            self.get_current_trip,
            self.get_trip_by_name,
            self.get_regular_trips,
            self.get_candidate_trips,
        ]

    def get_upcoming_trip(self) -> dict[str, Any] | None:
        """Retrieves the next trip that starts in the future — the one with the earliest start date that has not yet begun.
        Call this when the user asks about "my next trip", "upcoming trip", or "when am I travelling next".
        Returns null if there are no future trips scheduled.

        Returns:
            Null if no upcoming trip exists, or the nearest future trip object containing:
            - id (string): Unique identifier of the trip. Use it when looking up stays, flights, public holidays and others.
            - name (string): Title/name of the trip.
            - year (integer): Year of the trip.
            - start (string): Start date in YYYY-MM-DD format (e.g. `"2025-12-27"`).
            - end (string): End date in YYYY-MM-DD format (e.g. `"2025-12-31"`).
            - days (integer): Number of calendar days the trip spans (inclusive).
            - countries (list of strings): Countries visited on thh trip (excluding layovers).
            - notes (list of strings): Notes relevant for the trip.
        """
        trips = self.core_api_client.get_trips(
            trip_type="regular",
            include="notes",
            sort="oldest",
        )
        if isinstance(trips, dict) and trips.get("message"):
            return trips

        current_timestamp = int(time.time())
        for trip in trips:
            if trip.get("start") > current_timestamp:                
                system_tz = self._get_system_timezone()
                return self._extract_trip(trip, system_tz)
        
        return None

    def get_current_trip(self) -> dict[str, Any] | None:
        """Retrieves the trip that is currently in progress — i.e. the trip whose start date is in the past and end date is in the future relative to right now.
        Call this when the user asks about "my current trip", "where am I right now", or "what trip am I on".
        Returns null if no trip is active at this moment; in that case consider calling  get_upcoming_trip to find what is coming next.

        Returns:
            Null if no current trip exists, or the current trip object containing:
            - id (string): Unique identifier of the trip. Use it when looking up stays, flights, public holidays and others.
            - name (string): Title/name of the trip.
            - year (integer): Year of the trip.
            - start (string): Start date in YYYY-MM-DD format (e.g. `"2025-12-27"`).
            - end (string): End date in YYYY-MM-DD format (e.g. `"2025-12-31"`).
            - days (integer): Number of calendar days the trip spans (inclusive).
            - countries (list of strings): Countries visited on thh trip (excluding layovers).
            - notes (list of strings): Notes relevant for the trip.
        """
        trips = self.core_api_client.get_trips(
            trip_type="regular",
            include="notes",
            sort="oldest",
        )
        if isinstance(trips, dict) and trips.get("message"):
            return trips

        current_timestamp = int(time.time())
        for trip in trips:
            if trip.get("start") < current_timestamp and trip.get("end") > current_timestamp:
                system_tz = self._get_system_timezone()
                return self._extract_trip(trip, system_tz)
        
        return None   

    def get_trip_by_name(self, query: str) -> dict[str, Any] | None:
        """Finds a specific trip by searching the Travel Portal full-text search index for trips matching the given query, then fetches the full trip object for the best match.
        Use this when the user refers to a trip by destination or title — for example "my Egypt trip", "when did I go to Vietnam", or "the Sri Lanka journey".
        The search is powered by OpenSearch and handles partial words, fuzzy matches, and multi-word queries well.
        If no match is found, consider falling back to get_upcoming_trip or ask the user to clarify.

        Args:
            query: The search term to match against trip names and country names. Use a destination or keyword from the trip title, e.g. "Sri Lanka", "Egypt", "Grand Canyon", "Las Vegas". Do not pass a full sentence — use the key noun only.

        Returns:
            Null if no such trip exists, or the trip object containing:
            - id (string): Unique identifier of the trip. Use it when looking up stays, flights, public holidays and others.
            - name (string): Title/name of the trip.
            - year (integer): Year of the trip.
            - start (string): Start date in YYYY-MM-DD format (e.g. `"2025-12-27"`).
            - end (string): End date in YYYY-MM-DD format (e.g. `"2025-12-31"`).
            - days (integer): Number of calendar days the trip spans (inclusive).
            - countries (list of strings): Countries visited on thh trip (excluding layovers).
            - notes (list of strings): Notes relevant for the trip.
        """
        search_results = self.core_api_client.search(query=query, include="trip", limit=1)
        if isinstance(search_results, dict) and search_results.get("message"):
            return search_results

        trip_result = next((r for r in search_results if r.get("type") == "trip"), None)
        if trip_result is None:
            return None

        trip_id = (trip_result.get("entity") or {}).get("id")
        if not trip_id:
            return None

        trip = self.core_api_client.get_trip(trip_id)
        if isinstance(trip, dict) and trip.get("message"):
            return trip

        system_tz = self._get_system_timezone()
        return self._extract_trip(trip, system_tz)

    def get_regular_trips(self,
        year: int | None = None,
        sort: str | None = None
    ) -> list[dict[str, Any]] | None:
        """Retrieves a list of trips from Travel Portal.
        Use this tool when you need to browse, list, or inspect multiple trips at once — for example to answer questions like "how many trips did I take in 2024?" or "show me all my trips".
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
            - start (string): Start date in YYYY-MM-DD format (e.g. `"2025-12-27"`).
            - end (string): End date in YYYY-MM-DD format (e.g. `"2025-12-31"`).
            - days (integer): Number of calendar days the trip spans (inclusive).
            - countries (list of strings): Countries visited on thh trip (excluding layovers).
            - notes (list of strings): Notes relevant for the trip.
        """
        trips = self.core_api_client.get_trips(
            trip_type="regular",
            include="notes",
            year=year,
            sort=sort,
        )
        if isinstance(trips, dict) and trips.get("message"):
            return trips
        
        system_tz = self._get_system_timezone()
        return [t for trip in trips if (t := self._extract_trip(trip, system_tz)) is not None]

    def get_candidate_trips(self) -> list[dict[str, Any]] | None:
        """Retrieves a list of candidate trips from Travel Portal.
        Use this tool when you need to browse, list, or inspect multiple candidate trips at once — for example to answer questions like "are there any unplanned trips?" or "show me all my candidate trips".
        For a single specific trip use get_current_trip, get_upcoming_trip, or get_trip_by_name instead.
        
        Returns:
            A list of trip objects, where each object contains:
            - id (string): Unique identifier of the trip. Use it when looking up stays, flights, public holidays and others.
            - name (string): Title/name of the trip.
            - year (integer): Year of the trip.
            - start (string): Start date in YYYY-MM-DD format (e.g. `"2025-12-27"`).
            - end (string): End date in YYYY-MM-DD format (e.g. `"2025-12-31"`).
            - days (integer): Number of calendar days the trip spans (inclusive).
            - countries (list of strings): Countries visited on thh trip (excluding layovers).
            - notes (list of strings): Notes relevant for the trip.
        """
        trips = self.core_api_client.get_trips(
            trip_type="candidate",
            include="notes",
        )
        if isinstance(trips, dict) and trips.get("message"):
            return trips
        
        system_tz = self._get_system_timezone()
        return [t for trip in trips if (t := self._extract_trip(trip, system_tz)) is not None]

    def _extract_trip(self,
        trip: dict[str, Any],
        # All trips start and end in the the system timezone.
        system_tz: ZoneInfo,
    ) -> dict[str, Any] | None:
        extracted_trip = {
            "id": trip.get("id"),
            "name": trip.get("name"),
            "countries": trip.get("countries", []),
            "notes": [note.get("content") for note in trip.get("notes", [])],
        }

        if trip.get("year") is not None:
            start_epoch = trip.get("start")
            if self._get_maximum_timestamp() < start_epoch:
                return None
            
            end_epoch = trip.get("end") - 1

            start_date = datetime.fromtimestamp(start_epoch, tz=system_tz).date()
            end_date = datetime.fromtimestamp(end_epoch, tz=system_tz).date()

            extracted_trip["year"] = trip.get("year")
            extracted_trip["days"] = (end_date - start_date).days + 1
            extracted_trip["start"] = self._epoch_to_date(start_epoch, system_tz)
            extracted_trip["end"] = self._epoch_to_date(end_epoch, system_tz)
        elif not self._can_read_future():
            return None

        return extracted_trip
        