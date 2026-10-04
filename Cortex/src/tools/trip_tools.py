import time
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

    def get_trips(self, year: int | None = None, trip_type: str = "regular") -> list[dict[str, Any]]:
        """Retrieves a list of all trips from Travel Portal.

        Args:
            year: Optional filter by trip year (e.g., 2025).
            trip_type: Type of trips to retrieve, either 'regular' or 'candidate'. Defaults to 'regular'.

        Returns:
            A list of trip objects containing id, name, year, start, end, and countries.
        """
        return self.core_api_client.get_trips(year=year, trip_type=trip_type)

    def get_current_trip(self) -> dict[str, Any] | None:
        """Retrieves the currently active (ongoing) trip where the current time is between trip start and end dates.

        Returns:
            The current active trip object if one is in progress right now, or null if no trip is currently active.
        """
        current_timestamp = int(time.time())
        trips = self.core_api_client.get_trips(trip_type="regular")

        for trip in trips:
            start = trip.get("start")
            end = trip.get("end")
            if start is not None and end is not None and start <= current_timestamp <= end:
                return trip

        return None

    def get_upcoming_trip(self) -> dict[str, Any] | None:
        """Retrieves the next upcoming trip scheduled in the future, sorted by the earliest start date.

        Returns:
            The nearest upcoming trip object in the future, or null if there are no upcoming trips.
        """
        current_timestamp = int(time.time())
        trips = self.core_api_client.get_trips(trip_type="regular")

        upcoming_trips = [
            trip for trip in trips
            if trip.get("start") is not None and trip.get("start") > current_timestamp
        ]

        if not upcoming_trips:
            return None

        upcoming_trips.sort(key=lambda t: t.get("start", 0))
        return upcoming_trips[0]

    def find_trip_by_name(self, query: str) -> dict[str, Any] | None:
        """Finds a trip by searching for a match in the trip name or visited countries.

        Args:
            query: The search term (e.g., 'Las Vegas', 'Sri Lanka', 'Egypt').

        Returns:
            The matching trip object if found, or null if no match was found.
        """
        query_lower = query.strip().lower()
        trips = self.core_api_client.get_trips(trip_type="regular")

        for trip in trips:
            name = (trip.get("name") or "").lower()
            countries = [c.lower() for c in (trip.get("countries") or [])]
            if query_lower in name or any(query_lower in c for c in countries):
                return trip

        return None
