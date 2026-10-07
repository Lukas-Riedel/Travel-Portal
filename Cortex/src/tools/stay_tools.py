from datetime import datetime
from typing import Any
from zoneinfo import ZoneInfo

from src.service.core_api_client import CoreApiClient


class StayTools:
    def __init__(self, core_api_client: CoreApiClient):
        self.core_api_client = core_api_client

    def get_tools(self) -> list[Any]:
        return [
            self.get_stays_for_trip,
            self.get_all_stays,
        ]

    def get_stays_for_trip(self, trip_id: str) -> list[dict[str, Any]] | None:
        """Retrieves all accommodation stays for a specific trip.
        Call this when the user asks about where they stayed on a particular trip — for example "where did I stay in Japan?", "what hotels did I book for my Egypt trip?", or "show me the accommodation for my upcoming trip".
        Always resolve the trip ID first using trip tools (get_current_trip, get_upcoming_trip, or get_trip_by_name) before calling this function — never ask the user for a UUID.
        Returns an empty list if the trip has no stays recorded.

        Args:
            trip_id: The unique identifier of the trip whose stays should be retrieved.

        Returns:
            An empty list if no stays exist for the trip, or a list of stay objects where each object contains:
            - name (string): Name of the accommodation (e.g. "Jumeirah Burj Al Arab", "MSC Grandiosa").
            - address (string | null): Physical address of the property. Null when the stay is a cruise.
            - trip_id (string): Unique identifier of the parent trip.
            - trip_name (string): Name of the parent trip.
            - start (string): Check-in date in the user's local timezone (e.g. `"2026-10-08"`).
            - end (string): Check-out date in the user's local timezone (e.g. `"2026-10-12"`).
            - nights (integer): Number of nights spent at the accommodation.
        """
        trip = self.core_api_client.get_trip(trip_id)
        if isinstance(trip, dict) and trip.get("message"):
            return trip

        user_tz = self._get_user_timezone()
        return [self._extract_stay(stay, trip, user_tz) for stay in trip.get("stays", [])]

    def get_all_stays(self, year: int | None = None) -> list[dict[str, Any]]:
        """Retrieves all accommodation stays across all regular trips, optionally filtered by year.
        Call this when the user asks about accommodation across multiple trips — for example "where have I stayed this year?", "list all my hotels", or "show me every place I've slept on my travels".
        For stays on a single specific trip use get_stays_for_trip instead.
        Returns an empty list if no stays are recorded across any trip.

        Args:
            year: Filter stays to a specific calendar year (e.g. 2024). Omit or pass None to retrieve stays across all years.

        Returns:
            An empty list if no stays exist, or a flat list of stay objects ordered by trip, where each object contains:
            - name (string): Name of the accommodation (e.g. "Jumeirah Burj Al Arab", "MSC Grandiosa").
            - address (string | null): Physical address of the property. Null when the stay is a cruise.
            - trip_id (string): Unique identifier of the parent trip.
            - trip_name (string): Name of the parent trip.
            - start (string): Check-in date in the user's local timezone (e.g. `"2026-10-08"`).
            - end (string): Check-out date in the user's local timezone (e.g. `"2026-10-12"`).
            - nights (integer): Number of nights spent at the accommodation.
        """
        trips = self.core_api_client.get_trips(
            trip_type="regular",
            include="stays",
            year=year,
        )
        if isinstance(trips, dict) and trips.get("message"):
            return trips

        user_tz = self._get_user_timezone()
        return [
            self._extract_stay(stay, trip, user_tz)
            for trip in trips
            for stay in trip.get("stays", [])
        ]

    def _extract_stay(self,
        stay: dict[str, Any],
        trip: dict[str, Any],
        user_tz: ZoneInfo
    ) -> dict[str, Any]:
        start_epoch = stay.get("start")
        end_epoch = stay.get("end") - 1

        start_date = datetime.fromtimestamp(start_epoch, tz=user_tz).date()
        end_date = datetime.fromtimestamp(end_epoch, tz=user_tz).date()

        return {
            "name": stay.get("name"),
            "address": stay.get("address"),
            "trip_id": trip.get("id"),
            "trip_name": trip.get("name"),
            "start": self._epoch_to_date(start_epoch, user_tz),
            "end": self._epoch_to_date(end_epoch, user_tz),
            "nights": (end_date - start_date).days,
        }
        
    def _get_user_timezone(self) -> ZoneInfo:
        config = self.core_api_client.get_configuration()
        return ZoneInfo(config["homeLocation"]["timezone"])

    def _epoch_to_date(self, epoch: int | None, user_tz: ZoneInfo) -> str | None:
        if epoch is None:
            return None
        return datetime.fromtimestamp(epoch, tz=user_tz).strftime("%Y-%m-%d")
