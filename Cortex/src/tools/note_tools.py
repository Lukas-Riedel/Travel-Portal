from typing import Any
from src.service.core_api_client import CoreApiClient


class NoteTools:
    def __init__(self, core_api_client: CoreApiClient):
        self.core_api_client = core_api_client

    def get_tools(self) -> list[Any]:
        return [
            self.create_trip_note,
        ]

    def create_trip_note(self, trip_id: str, content: str) -> dict[str, Any]:
        """Creates a new Markdown note attached to a specific trip in Travel Portal.

        Args:
            trip_id: The unique identifier (UUID) of the trip.
            content: The text content of the note in Markdown format.

        Returns:
            The created note object with its identifier and details.
        """
        return self.core_api_client.create_trip_note(trip_id=trip_id, content=content)
