import math
import time
from datetime import datetime, timezone
from typing import Any

from src.service.core_api_client import CoreApiClient


class TripTools:
    def __init__(self, core_api_client: CoreApiClient):
        self.core_api_client = core_api_client

    def get_tools(self) -> list[Any]:
        return [
            self.get_trips,
            self.get_current_trip,
            self.get_upcoming_trip,
            self.find_trip_by_name,
        ]

    @staticmethod
    def epoch_to_iso(epoch: int) -> str:
        return datetime.fromtimestamp(epoch, tz=timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")

    @staticmethod
    def enrich_trip(trip: dict[str, Any]) -> dict[str, Any]:
        enriched = dict(trip)
        start = enriched.get("start")
        end = enriched.get("end")
        if start is not None:
            enriched["start_iso"] = TripTools.epoch_to_iso(start)
        if end is not None:
            enriched["end_iso"] = TripTools.epoch_to_iso(end)
        if start is not None and end is not None:
            enriched["duration_days"] = math.ceil((end - start) / 86400)
        return enriched

    def get_trips(self, year: int | None = None, trip_type: str = "regular", include: list[str] | None = None) -> list[dict[str, Any]]:
        """Retrieves a list of trips from Travel Portal. Use this tool when you need to browse,
        list, or inspect multiple trips at once — for example to answer questions like
        "how many trips did I take in 2024?" or "show me all my trips".
        For a single specific trip use get_current_trip, get_upcoming_trip, or find_trip_by_name instead.

        Args:
            year: Filter trips to a specific calendar year (e.g. 2025). Omit or pass None to
                retrieve trips across all years.
            trip_type: Which collection of trips to query.
                - 'regular' (default): confirmed, scheduled trips with real start/end dates.
                - 'candidate': trip ideas/drafts that have no dates yet.
                Prefer 'regular' unless the user explicitly asks about trip candidates or ideas.
            include: Optional list of additional entities to embed in each trip object.
                Only request what is actually needed — each entity adds latency and payload size.
                Available values: 'expenses', 'stays', 'flights', 'watchedFlights', 'fitness',
                'notes', 'highlights', 'statistics', 'tasks', 'publicHolidays'.
                Example: ["notes", "expenses"] to include notes and expenses in each trip.

        Returns:
            A list of trip objects. Each object always contains:
            - id (string, UUID): unique trip identifier — use this for follow-up actions.
            - name (string): descriptive trip title, e.g. "One Thousand Scents of Sri Lanka".
            - year (integer): calendar year of the trip.
            - countries (list of strings): country names visited, e.g. ["Japan", "South Korea"].
            - start (integer): trip start as Unix epoch seconds — do NOT show this to the user.
            - end (integer): trip end as Unix epoch seconds — do NOT show this to the user.
            - start_iso (string): trip start as ISO 8601 UTC, e.g. "2025-07-05T08:00:00Z" — use this for dates.
            - end_iso (string): trip end as ISO 8601 UTC, e.g. "2025-07-19T20:00:00Z" — use this for dates.
            - duration_days (integer): inclusive number of days the trip spans, e.g. 14.
            Plus any additional entity arrays requested via 'include'.
        """
        include_str = ",".join(include) if include else None
        trips = self.core_api_client.get_trips(year=year, trip_type=trip_type, include=include_str)
        return [self.enrich_trip(t) for t in trips]

    def get_current_trip(self, include: list[str] | None = None) -> dict[str, Any] | None:
        """Retrieves the trip that is currently in progress — i.e. the trip whose start date is in
        the past and end date is in the future relative to right now. Call this when the user asks
        about "my current trip", "where am I right now", or "what trip am I on".
        Returns null if no trip is active at this moment; in that case consider calling
        get_upcoming_trip to find what is coming next.

        Args:
            include: Optional list of additional entities to embed in the returned trip object.
                Only request what is actually needed — each entity adds latency and payload size.
                Available values: 'expenses', 'stays', 'flights', 'watchedFlights', 'fitness',
                'notes', 'highlights', 'statistics', 'tasks', 'publicHolidays'.
                Example: ["notes", "tasks"] to include notes and tasks.

        Returns:
            The active trip object, or null if no trip is ongoing right now. Fields:
            - id (string, UUID): unique trip identifier — use this for follow-up actions.
            - name (string): descriptive trip title.
            - year (integer): calendar year of the trip.
            - countries (list of strings): country names visited.
            - start_iso (string): ISO 8601 UTC start datetime, e.g. "2025-07-05T08:00:00Z".
            - end_iso (string): ISO 8601 UTC end datetime, e.g. "2025-07-19T20:00:00Z".
            - duration_days (integer): total length of the trip in days (inclusive).
            Plus any additional entity arrays requested via 'include'.
        """
        current_timestamp = int(time.time())
        include_str = ",".join(include) if include else None
        trips = self.core_api_client.get_trips(trip_type="regular", include=include_str)

        for trip in trips:
            start = trip.get("start")
            end = trip.get("end")
            if start is not None and end is not None and start <= current_timestamp <= end:
                return self.enrich_trip(trip)

        return None

    def get_upcoming_trip(self, include: list[str] | None = None) -> dict[str, Any] | None:
        """Retrieves the next trip that starts in the future — the one with the earliest start date
        that has not yet begun. Call this when the user asks about "my next trip", "upcoming trip",
        "when am I travelling next", or as a fallback when get_current_trip returns null.
        Returns null if there are no future trips scheduled.

        Args:
            include: Optional list of additional entities to embed in the returned trip object.
                Only request what is actually needed — each entity adds latency and payload size.
                Available values: 'expenses', 'stays', 'flights', 'watchedFlights', 'fitness',
                'notes', 'highlights', 'statistics', 'tasks', 'publicHolidays'.
                Example: ["stays", "flights"] to include accommodation and flight details.

        Returns:
            The nearest future trip object, or null if no upcoming trips exist. Fields:
            - id (string, UUID): unique trip identifier — use this for follow-up actions.
            - name (string): descriptive trip title.
            - year (integer): calendar year of the trip.
            - countries (list of strings): country names visited.
            - start_iso (string): ISO 8601 UTC start datetime, e.g. "2025-09-01T06:00:00Z".
            - end_iso (string): ISO 8601 UTC end datetime, e.g. "2025-09-14T22:00:00Z".
            - duration_days (integer): total length of the trip in days (inclusive).
            Plus any additional entity arrays requested via 'include'.
        """
        current_timestamp = int(time.time())
        include_str = ",".join(include) if include else None
        trips = self.core_api_client.get_trips(trip_type="regular", include=include_str)

        upcoming_trips = [
            trip for trip in trips
            if trip.get("start") is not None and trip.get("start") > current_timestamp
        ]

        if not upcoming_trips:
            return None

        upcoming_trips.sort(key=lambda t: t.get("start", 0))
        return self.enrich_trip(upcoming_trips[0])

    def find_trip_by_name(self, query: str, include: list[str] | None = None) -> dict[str, Any] | None:
        """Finds a specific trip by searching the Travel Portal full-text search index for trips
        matching the given query, then fetches the full trip object for the best match.
        Use this when the user refers to a trip by destination or title — for example "my Egypt trip",
        "when did I go to Vietnam", or "the Sri Lanka journey". The search is powered by OpenSearch
        and handles partial words, fuzzy matches, and multi-word queries well.
        If no match is found, consider falling back to get_upcoming_trip or ask the user to clarify.

        Args:
            query: The search term to match against trip names and country names.
                Use a destination or keyword from the trip title, e.g. "Sri Lanka", "Egypt",
                "Grand Canyon", "Las Vegas". Do not pass a full sentence — use the key noun only.
            include: Optional list of additional entities to embed in the returned trip object.
                Only request what is actually needed — each entity adds latency and payload size.
                Available values: 'expenses', 'stays', 'flights', 'watchedFlights', 'fitness',
                'notes', 'highlights', 'statistics', 'tasks', 'publicHolidays'.
                Example: ["highlights", "statistics"] to include highlights and trip statistics.

        Returns:
            The best-matching trip object, or null if no trip matched the query. Fields:
            - id (string, UUID): unique trip identifier — use this for follow-up actions.
            - name (string): descriptive trip title.
            - year (integer): calendar year of the trip.
            - countries (list of strings): country names visited.
            - start (integer): trip start as Unix epoch seconds — do NOT show this to the user.
            - end (integer): trip end as Unix epoch seconds — do NOT show this to the user.
            - start_iso (string): ISO 8601 UTC start datetime, e.g. "2024-03-10T10:00:00Z".
            - end_iso (string): ISO 8601 UTC end datetime, e.g. "2024-03-24T18:00:00Z".
            - duration_days (integer): total length of the trip in days (inclusive).
            Plus any additional entity arrays requested via 'include'.
        """
        search_results = self.core_api_client.search(query=query, include="trip", limit=1)

        trip_result = next(
            (r for r in search_results if r.get("type") == "trip"),
            None,
        )
        if trip_result is None:
            return None

        trip_id = (trip_result.get("entity") or {}).get("id")
        if not trip_id:
            return None

        include_str = ",".join(include) if include else None
        trip = self.core_api_client.get_trip(trip_id=trip_id, include=include_str)
        return self.enrich_trip(trip)
