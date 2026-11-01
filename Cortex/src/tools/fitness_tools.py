from typing import Any

from src.tools.base_tools import BaseCoreTools


class TripFitnessReadTools(BaseCoreTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to query and summarize daily fitness records for trips in Travel Portal. Activate and apply this skill when the user asks about steps, activity duration, or walking distance during a trip or across trips (e.g. "how many steps did I take in Japan?", "how far did I walk in 2024?", "show my daily walking distance on my Iceland trip").'

    def _get_supported_skill(self) -> str:
        return "trip.fitness.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_trip_fitness_summaries,
            self.get_trip_fitness_day_summaries,
        ]

    def get_trip_fitness_summaries(
        self,
        trip_id: str | None,
        year: int | None,
    ) -> list[dict[str, Any]]:
        """Retrieves summed fitness records, optionally filtered by trip or year.
        Use this when the user asks about their steps, activity duration, or distance for a trip or across trips - for example "how many steps did I take in Japan?", "how far did I walk in 2024?", or "summarize my activity on all trips".

        Args:
            trip_id: Filter the summary to a specific trip. Resolve the trip ID first using trip tools. Never ask the user for a UUID. Pass None to retrieve summaries across all regular trips.
            year: Filter trips to a specific calendar year (e.g. 2024). Pass None to retrieve summaries across all years. Ignored when trip_id is provided.

        Returns:
            A list of fitness summaries, one per matching trip, where each object contains:
            - trip_id (string): Unique identifier of the trip (e.g. `"47f43337-e0b8-4f3e-a52f-11e6fdf13b02"`).
            - trip_name (string): Name and year of the trip (e.g. `"Japan 2024"`).
            - steps (integer): Total steps recorded during the trip (e.g. `53421`).
            - seconds (integer): Total activity duration in seconds during the trip (e.g. `18240`).
            - distance (number): Total distance in kilometers during the trip (e.g. `38.6`).
        """
        if trip_id is not None:
            trip = self.core_client.get_trip(trip_id)
            self._verify_error_response(trip)
            trips = [trip]
        else:
            trips = self.core_client.get_trips(
                trip_type="regular",
                include="fitness",
                year=year,
            )
            self._verify_error_response(trips)

        return [
            {
                "trip_id": trip.get("id"),
                "trip_name": f"{trip.get('name')} {trip.get('year')}",
                "steps": sum(record["steps"] for record in trip.get("fitness", [])),
                "seconds": sum(record["seconds"] for record in trip.get("fitness", [])),
                "distance": sum(
                    record["distance"] for record in trip.get("fitness", [])
                ),
            }
            for trip in trips
            if self._is_trip_visible(trip)
        ]

    def get_trip_fitness_day_summaries(
        self,
        trip_id: str,
    ) -> list[dict[str, Any]]:
        """Retrieves the daily fitness records for a specific trip.
        Use this when the user asks about activity on individual trip days - for example "how many steps did I take on each day in Japan?" or "show my daily walking distance on my Iceland trip".
        Resolve the trip ID first using trip tools. Never ask the user for a UUID.

        Args:
            trip_id: The unique identifier of the trip whose daily fitness records should be retrieved.

        Returns:
            A list of daily fitness summaries in trip-day order, where each object contains:
            - trip_id (string): Unique identifier of the trip (e.g. `"47f43337-e0b8-4f3e-a52f-11e6fdf13b02"`).
            - trip_name (string): Name and year of the trip (e.g. `"Japan 2024"`).
            - day (integer): One-based day number in the trip (e.g. `1`).
            - steps (integer): Steps recorded on this day (e.g. `8421`).
            - seconds (integer): Activity duration in seconds recorded on this day (e.g. `3600`).
            - distance (number): Distance in kilometers recorded on this day (e.g. `6.2`).
        """
        trip = self.core_client.get_trip(trip_id)
        self._verify_error_response(trip)

        return [
            {
                "trip_id": trip.get("id"),
                "trip_name": f"{trip.get('name')} {trip.get('year')}",
                "day": day_number,
                "steps": fitness["steps"],
                "seconds": fitness["seconds"],
                "distance": fitness["distance"],
            }
            for day_number, fitness in enumerate(trip.get("fitness", []), start=1)
        ]
