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
        """Creates a new note attached to a specific trip in Travel Portal. Use this when the user
        asks to save, write down, or record something about a trip — for example "add a note to my
        Egypt trip", "save this for my upcoming trip", or "note that I need to pack sunscreen".
        Always resolve the trip first (via get_current_trip, get_upcoming_trip, or find_trip_by_name)
        to obtain the trip_id before calling this tool. Do not guess or invent a trip_id.
        The note content supports Markdown formatting (headings, lists, bold, etc.).

        Args:
            trip_id: The UUID of the trip to attach the note to, e.g.
                "26135e57-fe89-4a38-82d4-5e0ad0485e28". Obtain this from the 'id' field of a
                trip object returned by one of the trip lookup tools.
            content: The body of the note in Markdown format. Write clearly and concisely.
                Use Markdown lists, headings, or bold text where it improves readability.
                Example: "## Packing list\\n- Sunscreen\\n- Adapter plug\\n- Travel insurance docs"

        Returns:
            The created note object. Fields include the note's unique id and the stored content.
        """
        return self.core_api_client.create_trip_note(trip_id=trip_id, content=content)
