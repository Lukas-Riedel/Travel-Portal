from abc import ABC
from typing import Any

from src.tools.base_tools import BaseCoreTools


class BaseAirlineTools(BaseCoreTools, ABC):
    def _extract_airline(self, airline: dict[str, Any]) -> dict[str, Any]:
        return {
            "id": airline.get("id"),
            "name": airline.get("name"),
            "codes": airline.get("codes", []),
        }


class AirlineReadTools(BaseAirlineTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to query and view airlines stored in Travel Portal. Activate and apply this skill when the user asks about airlines, their names, or IATA codes (e.g. "what airlines do I have?", "show me all airlines", "what codes does WizzAir have?").'

    def _is_enabled_by_default(self) -> bool:
        return False

    def _get_supported_skill(self) -> str:
        return "airline.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_airlines,
        ]

    def get_airlines(self) -> list[dict[str, Any]]:
        """Retrieves all airlines stored in Travel Portal.
        Use this when the user asks about airlines (e.g. "what airlines do I have?",
        "show me all airlines", "list airlines", "what codes does WizzAir have?").

        Returns:
            A list of airline objects, where each object contains:
            - id (string): Unique airline identifier.
            - name (string): Name of the airline (e.g. `"WizzAir"`).
            - codes (array of strings): IATA codes assigned to this airline (e.g. `["W4", "W6", "W9"]`).
        """
        airlines = self.core_client.get_airlines()
        if self._is_error_response(airlines):
            return airlines

        return [self._extract_airline(a) for a in airlines]


class AirlineEditTools(BaseAirlineTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to create and update airlines in Travel Portal. Activate and apply this skill when the user asks to add, rename, update the logo of, or assign a code to an airline (e.g. "add a new airline", "rename WizzAir", "assign code FR to Ryanair").'

    def _is_enabled_by_default(self) -> bool:
        return False

    def _get_supported_skill(self) -> str:
        return "airline.edit"

    def _get_tools(self) -> list[Any]:
        return [
            self.create_airline,
            self.update_airline_name,
            self.update_airline_logo,
            self.assign_airline_code,
        ]

    def create_airline(self, name: str, logo: str | None = None) -> dict[str, Any]:
        """Creates a new airline in Travel Portal.
        Use this when the user asks to add or create an airline
        (e.g. "add WizzAir", "create a new airline called Ryanair").

        Args:
            name: The name of the airline (e.g. "WizzAir").
            logo: SVG logo of the airline. Pass None to create the airline without a logo.

        Returns:
            The created airline object containing:
            - id (string): Unique airline identifier.
            - name (string): Name of the airline (e.g. `"WizzAir"`).
            - codes (array of strings): IATA codes assigned to this airline (e.g. `["W4", "W6", "W9"]`).
        """
        airline = self.core_client.create_airline(name=name, logo=logo)
        if self._is_error_response(airline):
            return airline

        return self._extract_airline(airline)

    def update_airline_name(self, airline_id: str, name: str) -> dict[str, Any]:
        """Updates the name of an existing airline in Travel Portal.
        Use this when the user asks to rename an airline or correct its name
        (e.g. "rename WizzAir to Wizz Air", "fix the name of the airline").
        Always resolve the airline first using get_airlines. Never ask the user for a UUID.

        Args:
            airline_id: The `id` of the airline as returned by get_airlines.
            name: The new name of the airline (e.g. "Wizz Air").

        Returns:
            The updated airline object containing:
            - id (string): Unique airline identifier.
            - name (string): Name of the airline (e.g. `"WizzAir"`).
            - codes (array of strings): IATA codes assigned to this airline (e.g. `["W4", "W6", "W9"]`).
        """
        airline = self.core_client.update_airline(airline_id=airline_id, name=name)
        if self._is_error_response(airline):
            return airline

        return self._extract_airline(airline)

    def update_airline_logo(self, airline_id: str, logo: str) -> dict[str, Any]:
        """Updates the SVG logo of an existing airline in Travel Portal.
        Use this when the user asks to set or replace the logo of an airline
        (e.g. "set the logo of WizzAir", "update the logo for Ryanair").
        Always resolve the airline first using get_airlines. Never ask the user for a UUID.

        Args:
            airline_id: The `id` of the airline as returned by get_airlines.
            logo: The new SVG logo as a string (e.g. "<svg xmlns=\\"http://www.w3.org/2000/svg\\" .../>").

        Returns:
            The updated airline object containing:
            - id (string): Unique airline identifier.
            - name (string): Name of the airline (e.g. `"WizzAir"`).
            - codes (array of strings): IATA codes assigned to this airline (e.g. `["W4", "W6", "W9"]`).
        """
        airline = self.core_client.update_airline(airline_id=airline_id, logo=logo)
        if self._is_error_response(airline):
            return airline

        return self._extract_airline(airline)

    def assign_airline_code(self, airline_id: str, code: str) -> dict[str, Any]:
        """Assigns an IATA code to an existing airline in Travel Portal.
        Use this when the user asks to add or assign a code to an airline
        (e.g. "assign code W6 to WizzAir", "add IATA code FR to Ryanair").
        Always resolve the airline first using get_airlines. Never ask the user for a UUID.

        Args:
            airline_id: The `id` of the airline as returned by get_airlines.
            code: The IATA code to assign to the airline (e.g. "W6").

        Returns:
            The updated airline object containing:
            - id (string): Unique airline identifier.
            - name (string): Name of the airline (e.g. `"WizzAir"`).
            - codes (array of strings): IATA codes assigned to this airline (e.g. `["W4", "W6", "W9"]`).
        """
        airline = self.core_client.create_airline_code(
            airline_id=airline_id,
            code=code,
        )
        if self._is_error_response(airline):
            return airline

        return self._extract_airline(airline)
