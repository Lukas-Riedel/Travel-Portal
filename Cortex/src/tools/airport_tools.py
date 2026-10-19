from abc import ABC
from typing import Any

from src.tools.base_tools import BaseCoreTools


class BaseAirportTools(BaseCoreTools, ABC):
    def _extract_airport(self, airport: dict[str, Any]) -> dict[str, Any]:
        return {
            "id": airport.get("id"),
            "name": airport.get("longName"),
            "code": airport.get("code"),
            "country": airport.get("country"),
        }


class AirportReadTools(BaseAirportTools):
    def _get_supported_skill(self) -> str:
        return "airport.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_airports,
        ]

    def get_airports(self) -> list[dict[str, Any]]:
        """Retrieves all airports stored in Travel Portal.
        Use this when the user asks about airports (e.g. "what airport have I visited?",
        "show me all airports", "list airports", "what is the full name of PRG?").

        Returns:
            A list of airport objects, where each object contains:
            - id (string): Unique airport identifier.
            - name (string): Full official name of the airport (e.g. `"Václav Havel Prague Airport"`).
            - code (string): IATA code (e.g. `"PRG"`).
            - country (string): Country where the airport is located (e.g. `"Czechia"`).
        """
        airports = self.core_client.get_airports()
        if self._is_error_response(airports):
            return airports

        return [self._extract_airport(a) for a in airports]


class AirportEditTools(BaseAirportTools):
    def _get_supported_skill(self) -> str:
        return "airport.edit"

    def _get_tools(self) -> list[Any]:
        return [
            self.update_airport_name,
        ]

    def update_airport_name(self, airport_id: str, name: str) -> dict[str, Any]:
        """Updates the full official name of an existing airport in Travel Portal.
        Use this when the user asks to rename an airport or correct its full name
        (e.g. "rename PRG to Václav Havel Prague Airport", "fix the name of Dubai airport").
        Always resolve the airport first using get_airports - never ask the user for a UUID.

        Args:
            airport_id: The `id` of the airport as returned by get_airports.
            name: The new full official name of the airport (e.g. "Václav Havel Prague Airport").

        Returns:
            The updated airport object containing:
            - id (string): Unique airport identifier.
            - name (string): Full official name of the airport (e.g. `"Václav Havel Prague Airport"`).
            - code (string): IATA code (e.g. `"PRG"`).
            - country (string): Country where the airport is located (e.g. `"Czechia"`).
        """
        airport = self.core_client.update_airport(
            airport_id=airport_id,
            long_name=name,
        )
        if self._is_error_response(airport):
            return airport

        return self._extract_airport(airport)
