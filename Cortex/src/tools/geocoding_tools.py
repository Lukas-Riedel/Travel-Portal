from typing import Any

from src.tools.base_tools import BaseCoreTools


class GeocodingReadTools(BaseCoreTools):
    def _get_description(self) -> str | None:
        return "Provides capabilities to look up the timezone for an address or location using geocoding. Activate and apply this skill when the user asks which timezone applies to a location or needs a location's timezone for a date or time operation."

    def _is_enabled_by_default(self) -> bool:
        return False

    def _get_supported_skill(self) -> str:
        return "geocoding.read"

    def _get_tools(self) -> list[Any]:
        return [self.get_location_timezone]

    def get_location_timezone(self, address: str) -> str:
        """Retrieves the timezone for an address or location.
        Use this when the user asks which timezone applies to a location or needs its timezone for a date or time operation.

        Args:
            address: Address or location name to resolve (e.g. `"Prague, Czech Republic"`).

        Returns:
            The IANA timezone identifier of the location.
        """
        location = self.core_client.get_coordinates(address=address)
        if self._is_error_response(location):
            return location

        return location.get("timezone")
