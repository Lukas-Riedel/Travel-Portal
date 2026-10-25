from abc import ABC
from typing import Any

from src.tools.base_tools import BaseCoreTools


class BaseNoteTools(BaseCoreTools, ABC):
    def _extract_note(
        self,
        note: dict[str, Any],
    ) -> dict[str, Any]:
        return {
            "id": note.get("id"),
            "content": note.get("content"),
        }


class TripNoteReadTools(BaseNoteTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to query and inspect notes, memos, reminders, and observations recorded for a specific trip in Travel Portal. Activate and apply this skill when the user asks about notes, memos, or information written down for a specific trip (e.g. "what notes did I take during my Japan trip?", "show me notes for Egypt", "what did I note down during my Japan trip?").'

    def _get_supported_skill(self) -> str:
        return "trip.note.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_notes_for_trip,
        ]

    def get_notes_for_trip(self, trip_id: str) -> list[dict[str, Any]]:
        """Retrieves all notes for a specific trip.
        Call this when the user asks about notes, memos, or observations recorded on a particular trip - for example "what notes did I take in Japan?", "show me notes for my Egypt trip", or "did I write anything down for my upcoming trip?".
        Always resolve the trip ID first using trip tools before calling this function. Never ask the user for a UUID.
        Returns an empty list if the trip has no notes recorded.

        Args:
            trip_id: The unique identifier of the trip whose notes should be retrieved.

        Returns:
            An empty list if no notes exist for the trip, or a list of note objects where each object contains:
            - id (string): Unique identifier of the note. Use it when updating or deleting the note.
            - content (string): Markdown-formatted content of the note.
        """
        trip = self.core_client.get_trip(trip_id=trip_id)
        self._verify_error_response(trip)

        return [self._extract_note(note) for note in trip.get("notes", [])]


class TripNoteEditTools(BaseNoteTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to create, update, and delete notes, memos, and reminders for trips in Travel Portal. Activate and apply this skill when the user asks to add, record, modify, edit, or delete a note for a trip (e.g. "add a note to my Japan trip", "update the note about train tickets", "delete the note about car rental").'

    def _get_supported_skill(self) -> str:
        return "trip.note.edit"

    def _get_tools(self) -> list[Any]:
        return [
            self.create_trip_note,
            self.update_trip_note_content,
            self.delete_trip_note,
        ]

    def create_trip_note(self, trip_id: str, content: str) -> dict[str, Any]:
        """Creates a new note for a specific trip in Travel Portal.
        Call this when the user asks to add, record, save, or write a note, memo, reminder, or observation for a trip (e.g. "add a note to my Japan trip: remember to buy JR pass", "note down for Egypt that museum is closed on Mondays").
        Always resolve the trip ID first using trip tools. Never ask the user for a UUID.

        Args:
            trip_id: The unique identifier of the trip to which the note should be added.
            content: The Markdown-formatted content of the note.

        Returns:
            The created note object containing:
            - id (string): Unique identifier of the note.
            - content (string): Markdown-formatted content of the note.
        """
        note = self.core_client.create_trip_note(
            trip_id=trip_id,
            content=content,
        )
        self._verify_error_response(note)

        return self._extract_note(note)

    def update_trip_note_content(
        self, trip_id: str, note_id: str, content: str
    ) -> dict[str, Any]:
        """Updates the content of an existing trip note in Travel Portal.
        Call this when the user asks to modify, rewrite, append to, or edit an existing note for a trip (e.g. "update the note in Japan to add room number 402", "change my note about tickets").
        Always resolve the trip ID first and find the note ID via get_notes_for_trip. Never ask the user for a UUID.

        Args:
            trip_id: The unique identifier of the trip the note belongs to.
            note_id: The unique identifier of the note to update.
            content: The complete new Markdown content for the note (pass the full updated text).

        Returns:
            The updated note object containing:
            - id (string): Unique identifier of the note.
            - content (string): Markdown-formatted content of the note.
        """
        note = self.core_client.update_trip_note(
            trip_id=trip_id,
            note_id=note_id,
            content=content,
        )
        self._verify_error_response(note)

        return self._extract_note(note)

    def delete_trip_note(self, trip_id: str, note_id: str) -> dict[str, Any]:
        """Deletes an existing note from a trip in Travel Portal.
        Call this when the user asks to delete, remove, or discard a note from a trip (e.g. "delete the note about car rental in Iceland", "remove my note on packing list from the Egypt trip").
        Always resolve the trip ID first and find the note ID via get_notes_for_trip. Never ask the user for a UUID.

        Args:
            trip_id: The unique identifier of the trip the note belongs to.
            note_id: The unique identifier of the note to delete.

        Returns:
            A confirmation object indicating successful deletion containing:
            - deleted (boolean): True if deletion succeeded.
            - id (string): Unique identifier of the deleted note.
        """
        result = self.core_client.delete_trip_note(
            trip_id=trip_id,
            note_id=note_id,
        )
        self._verify_error_response(result)

        return {"deleted": True, "id": note_id}


class PlaceNoteReadTools(BaseNoteTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to query and inspect notes, memos, reminders, and observations recorded for places in Travel Portal. Activate and apply this skill when the user asks about place notes, memos, or information written down for a specific place (e.g. "what notes do I have for Prague?", "show me notes for Angkor Wat", "did I write any tips for Eiffel Tower?").'

    def _get_supported_skill(self) -> str:
        return "place.note.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_notes_for_place,
        ]

    def get_notes_for_place(self, place_id: str) -> list[dict[str, Any]]:
        """Retrieves all notes for a specific place.
        Call this when the user asks about notes, memos, tips, or observations recorded on a particular place - for example "what notes do I have for Prague?", "show me notes for Angkor Wat", or "did I write any tips for Eiffel Tower?".
        Always resolve the place ID first using place tools before calling this function. Never ask the user for a UUID.
        Returns an empty list if the place has no notes recorded.

        Args:
            place_id: The unique identifier of the place whose notes should be retrieved.

        Returns:
            An empty list if no notes exist for the place, or a list of note objects where each object contains:
            - id (string): Unique identifier of the note. Use it when updating or deleting the note.
            - content (string): Markdown-formatted content of the note.
        """
        place = self.core_client.get_place(place_id=place_id)
        self._verify_error_response(place)

        return [self._extract_note(note) for note in place.get("notes", [])]


class PlaceNoteEditTools(BaseNoteTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to create, update, and delete notes, memos, and reminders for places in Travel Portal. Activate and apply this skill when the user asks to add, record, modify, edit, or delete a note for a place (e.g. "add a note to Prague", "update the note about opening hours for Angkor Wat", "delete the note about parking in Vienna").'

    def _get_supported_skill(self) -> str:
        return "place.note.edit"

    def _get_tools(self) -> list[Any]:
        return [
            self.create_place_note,
            self.update_place_note_content,
            self.delete_place_note,
        ]

    def create_place_note(self, place_id: str, content: str) -> dict[str, Any]:
        """Creates a new note for a specific place in Travel Portal.
        Call this when the user asks to add, record, save, or write a note, memo, reminder, or tip for a place (e.g. "add a note to Prague: visit the library at Strahov", "note down for Angkor Wat to buy tickets online").
        Always resolve the place ID first using place tools. Never ask the user for a UUID.

        Args:
            place_id: The unique identifier of the place to which the note should be added.
            content: The Markdown-formatted content of the note.

        Returns:
            The created note object containing:
            - id (string): Unique identifier of the note.
            - content (string): Markdown-formatted content of the note.
        """
        note = self.core_client.create_place_note(
            place_id=place_id,
            content=content,
        )
        self._verify_error_response(note)

        return self._extract_note(note)

    def update_place_note_content(
        self, place_id: str, note_id: str, content: str
    ) -> dict[str, Any]:
        """Updates the content of an existing place note in Travel Portal.
        Call this when the user asks to modify, rewrite, append to, or edit an existing note for a place (e.g. "update the note for Prague to add open hours", "change my note about tickets for Angkor Wat").
        Always resolve the place ID first and find the note ID via get_notes_for_place. Never ask the user for a UUID.

        Args:
            place_id: The unique identifier of the place the note belongs to.
            note_id: The unique identifier of the note to update.
            content: The complete new Markdown content for the note (pass the full updated text).

        Returns:
            The updated note object containing:
            - id (string): Unique identifier of the note.
            - content (string): Markdown-formatted content of the note.
        """
        note = self.core_client.update_place_note(
            place_id=place_id,
            note_id=note_id,
            content=content,
        )
        self._verify_error_response(note)

        return self._extract_note(note)

    def delete_place_note(self, place_id: str, note_id: str) -> dict[str, Any]:
        """Deletes an existing note from a place in Travel Portal.
        Call this when the user asks to delete, remove, or discard a note from a place (e.g. "delete the note about parking in Vienna", "remove my note on ticket booking from the Prague place").
        Always resolve the place ID first and find the note ID via get_notes_for_place. Never ask the user for a UUID.

        Args:
            place_id: The unique identifier of the place the note belongs to.
            note_id: The unique identifier of the note to delete.

        Returns:
            A confirmation object indicating successful deletion containing:
            - deleted (boolean): True if deletion succeeded.
            - id (string): Unique identifier of the deleted note.
        """
        result = self.core_client.delete_place_note(
            place_id=place_id,
            note_id=note_id,
        )
        self._verify_error_response(result)

        return {"deleted": True, "id": note_id}
