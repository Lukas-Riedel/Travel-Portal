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
        if self._is_error_response(labels):
            return labels

        return [self._extract_label(label) for label in labels]


class PlaceLabelReadTools(BaseLabelTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to query and inspect labels assigned to places in Travel Portal. Activate and apply this skill when the user asks about labels assigned to a specific place (e.g. "what labels does Prague have?", "show me the labels for Angkor Wat").'

    def _get_supported_skill(self) -> str:
        return "place.label.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_labels_for_place,
        ]

    def get_labels_for_place(self, place_id: str) -> list[dict[str, Any]]:
        """Retrieves the labels assigned to a specific place.
        Use this when the user asks which labels are assigned to a place - for example "what labels does Prague have?" or "show me the labels for Angkor Wat". Resolve the place first using place tools. Never ask the user for a UUID.

        Args:
            place_id: The unique identifier of the place, resolved using place tools.

        Returns:
            A list of label objects for the place, where each object contains:
            - id (string): Unique label identifier.
            - name (string): Label name (e.g. `"City"`).
        """
        place = self.core_client.get_place(place_id=place_id)
        if self._is_error_response(place):
            return place

        return [self._extract_label(label) for label in place.get("labels", [])]


class PlaceLabelEditTools(BaseLabelTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to assign existing labels to places and create new labels after user confirmation. Activate and apply this skill when the user asks to label a place, assign an existing label, or create a new label for a place (e.g. "label Prague as a city", "add the City label to Bangkok", "create a new label called UNESCO Site for Angkor Wat").'

    def _get_supported_skill(self) -> str:
        return "place.label.edit"

    def _get_tools(self) -> list[Any]:
        return [
            self.assign_label_to_place,
        ]

    def assign_label_to_place(
        self,
        place_id: str,
        label_name: str,
    ) -> dict[str, Any]:
        """Assigns an existing label to a place or creates a new label after user confirmation.
        Use this when the user asks to apply a label to a place - for example "label Prague as a city" or "add the City label to Angkor Wat". Resolve the place first using place tools and select an existing label from the available labels. If the label does not exist, call this only after the user explicitly confirms creating it. Never ask the user for a UUID.

        Args:
            place_id: The unique identifier of the place, resolved using place tools.
            label_name: The exact name of an existing label selected from available labels, or the new label name after explicit user confirmation (e.g. "City").

        Returns:
            The assigned label object containing:
            - id (string): Unique label identifier.
            - place_id (string): Unique identifier of the place.
            - place_name (string): Name of the place (e.g. `"Prague"`).
            - name (string): Label name (e.g. `"City"`).
        """
        assigned_label = self.core_client.create_place_label(
            place_id=place_id,
            name=label_name,
        )
        if self._is_error_response(assigned_label):
            return assigned_label

        return self._extract_label(assigned_label)
