from datetime import datetime, tzinfo
from typing import Any

from src.tools.base_tools import BaseCoreTools


class StayReadTools(BaseCoreTools):
    def _get_supported_skill(self) -> str:
        return "trip.stay.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_stays,
            self.get_cruises,
        ]

    def get_stays(self, trip_id: str | None, year: int | None) -> list[dict[str, Any]]:
        """Retrieves accommodation stays, optionally filtered by trip or year.
        Call this when the user asks about where they stayed - for example "where did I stay in Japan?", "what hotels did I book for my Egypt trip?", "where have I stayed this year?", or "list all my hotels".
        Returns an empty list if no stays are recorded.

        Args:
            trip_id: Filter stays to a specific trip. Resolve the trip ID first using trip tools. Never ask the user for a UUID.
                Pass None to retrieve stays across all regular trips.
            year: Filter stays to a specific calendar year (e.g. 2024). Pass None to retrieve stays across all years.
                Ignored when trip_id is provided.

        Returns:
            An empty list if no stays exist, or a list of stay objects where each object contains:
            - name (string): Name of the accommodation (e.g. `"Jumeirah Burj Al Arab"`, `"MSC Grandiosa"`).
            - address (string | null): Physical address of the property. Null when the stay is a cruise.
            - trip_id (string): Unique identifier of the parent trip.
            - trip_name (string): Name and year of the parent trip.
            - start (string): Check-in date in the local timezone (e.g. `"2026-10-08"`).
            - end (string): Check-out date in the local timezone (e.g. `"2026-10-12"`).
            - nights (integer): Number of nights spent at the accommodation.
        """
        if trip_id is not None:
            trip = self.core_client.get_trip(trip_id)
            if self._is_error_response(trip):
                return trip
            trips = [trip]
        else:
            trips = self.core_client.get_trips(
                trip_type="regular",
                include="stays",
                year=year,
            )
            if self._is_error_response(trips):
                return trips

        system_tz = self._get_system_timezone()
        return [
            self._extract_stay(stay=stay, trip=trip, system_tz=system_tz)
            for trip in trips
            if self._is_trip_visible(trip)
            for stay in trip.get("stays", [])
        ]

    def get_cruises(
        self, trip_id: str | None, year: int | None
    ) -> list[dict[str, Any]]:
        """Retrieves cruises, optionally filtered by trip or year.
        Call this when the user asks specifically about cruises or ships - for example "which cruises have I been on?", "list all my cruises", "what ships did I sail on this year?", or "did I go on a cruise during my Mediterranean trip?".
        Use get_stays instead when the user asks about accommodation in general, including hotels.
        Returns an empty list if no cruises are recorded.

        Args:
            trip_id: Filter cruises to a specific trip. Resolve the trip ID first using trip tools. Never ask the user for a UUID.
                Pass None to retrieve cruises across all regular trips.
            year: Filter cruises to a specific calendar year (e.g. 2024). Pass None to retrieve cruises across all years.
                Ignored when trip_id is provided.

        Returns:
            An empty list if no cruises exist, or a list of cruise objects where each object contains:
            - name (string): Name of the ship (e.g. `"MSC Grandiosa"`).
            - trip_id (string): Unique identifier of the parent trip.
            - trip_name (string): Name and year of the parent trip.
            - start (string): Embarkation date in the local timezone (e.g. `"2026-10-08"`).
            - end (string): Disembarkation date in the local timezone (e.g. `"2026-10-12"`).
            - nights (integer): Number of nights spent on the cruise.
        """
        if trip_id is not None:
            trip = self.core_client.get_trip(trip_id)
            if self._is_error_response(trip):
                return trip
            trips = [trip]
        else:
            trips = self.core_client.get_trips(
                trip_type="regular",
                include="stays",
                year=year,
            )
            if self._is_error_response(trips):
                return trips

        system_tz = self._get_system_timezone()
        return [
            {
                key: value
                for key, value in self._extract_stay(
                    stay=stay, trip=trip, system_tz=system_tz
                ).items()
                if key != "address"
            }
            for trip in trips
            if self._is_trip_visible(trip)
            for stay in trip.get("stays", [])
            if stay.get("address") is None
        ]

    def _extract_stay(
        self,
        stay: dict[str, Any],
        trip: dict[str, Any],
        system_tz: tzinfo,
    ) -> dict[str, Any]:
        start_epoch = stay.get("start")
        end_epoch = stay.get("end") - 1

        start_date = datetime.fromtimestamp(start_epoch, tz=system_tz).date()
        end_date = datetime.fromtimestamp(end_epoch, tz=system_tz).date()

        return {
            "name": stay.get("name"),
            "address": stay.get("address"),
            "trip_id": trip.get("id"),
            "trip_name": f"{trip.get('name')} {trip.get('year')}",
            "start": self._epoch_to_date(epoch=start_epoch, user_tz=system_tz),
            "end": self._epoch_to_date(epoch=end_epoch, user_tz=system_tz),
            "nights": (end_date - start_date).days,
        }
