from abc import ABC
from typing import Any

from src.tools.base_tools import BaseCoreTools


class BaseLabelTools(BaseCoreTools, ABC):
    def _extract_label(self, label: dict[str, Any]) -> dict[str, Any]:
        return {
            "id": label.get("id"),
            "name": label.get("name"),
        }


class LabelReadTools(BaseLabelTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to query and inspect labels available in Travel Portal. Activate and apply this skill when the user asks about labels or wants to see the available labels (e.g. "what labels do I have?", "show me all labels", "which labels can I use for places?").'

    def _get_supported_skill(self) -> str:
        return "label.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_labels,
        ]

    def get_labels(self) -> list[dict[str, Any]]:
        """Retrieves all labels available in Travel Portal.
        Use this when the user asks to see available labels - for example "what labels do I have?" or "show me all labels".

        Returns:
            A list of label objects, where each object contains:
            - id (string): Unique label identifier.
            - name (string): Label name (e.g. `"City"`).
        """
        labels = self.core_client.get_labels()
        self._verify_error_response(labels)

        return [self._extract_label(label) for label in labels]


class PlaceLabelReadTools(BaseLabelTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to inspect automatically matched labels for places in Travel Portal. Activate and apply this skill when the user asks what labels a place has (e.g. "what labels does Prague have?", "show me the labels for Angkor Wat"). Place labels are derived from the similarity of place photos to the example photos of each label and cannot be manually assigned or removed.'

    def _get_supported_skill(self) -> str:
        return "place.label.read"

    def _get_tools(self) -> list[Any]:
        return [self.get_labels_for_place]

    def get_labels_for_place(self, place_id: str) -> list[dict[str, Any]]:
        """Retrieves the labels automatically matched to a specific place.
        Resolve the place first using place tools. Never ask the user for a UUID.

        Args:
            place_id: The unique identifier of the place, resolved using place tools.

        Returns:
            A list of label objects for the place, where each object contains:
            - id (string): Unique label identifier.
            - name (string): Label name (e.g. `"City"`).
        """
        place = self.core_client.get_place(place_id=place_id)
        self._verify_error_response(place)

        return [self._extract_label(label) for label in place.get("labels", [])]


class LabelEditTools(BaseLabelTools):
    def _get_description(self) -> str | None:
        return "Provides capabilities to create standalone labels in Travel Portal. Activate and apply this skill when the user asks to create a label. Labels are matched to places automatically by the similarity of place photos to the example photos of the label, which are chosen in Travel Portal; they cannot be manually assigned to or removed from individual places."

    def _get_supported_skill(self) -> str:
        return "label.edit"

    def _get_tools(self) -> list[Any]:
        return [self.create_label]

    def create_label(self, name: str) -> dict[str, Any]:
        """Creates a standalone label.
        Use this when the user asks to create a label (e.g. "create a label called City").
        The new label has no places until its example photos are chosen in Travel Portal. Labels are matched to places automatically and cannot be manually assigned to places.

        Args:
            name: Name of the label to create (e.g. "City").

        Returns:
            The label object containing:
            - id (string): Unique label identifier.
            - name (string): Label name (e.g. `"City"`).
        """
        label = self.core_client.create_label(name=name)
        self._verify_error_response(label)

        return self._extract_label(label)
