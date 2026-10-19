from datetime import datetime
from typing import Any
from zoneinfo import ZoneInfo

from src.tools.base_tools import BaseCoreTools


class CarRentalReadTools(BaseCoreTools):
    def _get_supported_skill(self) -> str:
        return "trip.carrental.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_car_rentals,
        ]

    def get_car_rentals(
        self, trip_id: str | None, year: int | None
    ) -> list[dict[str, Any]]:
        """Retrieves car rentals, optionally filtered by trip or year.
        Call this when the user asks about rented cars - for example "what car did I rent in Iceland?", "did I have a rental car in Sardinia?", "which cars have I rented this year?", or "list all my car rentals".
        Returns an empty list if no car rentals are recorded.

        Args:
            trip_id: Filter car rentals to a specific trip. Resolve the trip ID first using trip tools. Never ask the user for a UUID.
                Pass None to retrieve car rentals across all regular trips.
            year: Filter car rentals to a specific calendar year (e.g. 2024). Pass None to retrieve car rentals across all years.
                Ignored when trip_id is provided.

        Returns:
            An empty list if no car rentals exist, or a list of car rental objects where each object contains:
            - model (string): Model of the rented car (e.g. "Toyota Yaris", "Skoda Octavia").
            - trip_id (string): Unique identifier of the parent trip.
            - trip_name (string): Name and year of the parent trip.
            - start (string): First day of the rental in the local timezone (e.g. `"2026-10-08"`).
            - end (string): Last day of the rental (the return day) in the local timezone (e.g. `"2026-10-12"`).
            - days (integer): Number of days the car was rented, including both the first and the return day.
        """
        if trip_id is not None:
            trip = self.core_client.get_trip(trip_id)
            if self._is_error_response(trip):
                return trip
            trips = [trip]
        else:
            trips = self.core_client.get_trips(
                trip_type="regular",
                include="carRentals",
                year=year,
            )
            if self._is_error_response(trips):
                return trips

        system_tz = self._get_system_timezone()
        return [
            self._extract_car_rental(
                car_rental=car_rental, trip=trip, system_tz=system_tz
            )
            for trip in trips
            if self._is_trip_visible(trip)
            for car_rental in trip.get("carRentals", [])
        ]

    def _extract_car_rental(
        self,
        car_rental: dict[str, Any],
        trip: dict[str, Any],
        system_tz: ZoneInfo,
    ) -> dict[str, Any]:
        start_epoch = car_rental.get("start")
        end_epoch = car_rental.get("end") - 1

        start_date = datetime.fromtimestamp(start_epoch, tz=system_tz).date()
        end_date = datetime.fromtimestamp(end_epoch, tz=system_tz).date()

        return {
            "model": car_rental.get("model"),
            "trip_id": trip.get("id"),
            "trip_name": f"{trip.get('name')} {trip.get('year')}",
            "start": self._epoch_to_date(epoch=start_epoch, user_tz=system_tz),
            "end": self._epoch_to_date(epoch=end_epoch, user_tz=system_tz),
            "days": (end_date - start_date).days + 1,
        }
