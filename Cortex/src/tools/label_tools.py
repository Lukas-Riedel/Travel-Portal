from abc import ABC
from typing import Any

from src.tools.base_tools import BaseCoreTools


class BaseLabelTools(BaseCoreTools, ABC):
    def _extract_label(self, label: dict[str, Any]) -> dict[str, Any]:
        return {
            "id": label.get("id"),
            "name": label.get("name"),
            "keywords": label.get("metadata", {}).get("keywords"),
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
            - keywords (list): Keywords used to match the label to place highlights.
        """
        labels = self.core_client.get_labels()
        self._verify_error_response(labels)

        return [self._extract_label(label) for label in labels]


class PlaceLabelReadTools(BaseLabelTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to inspect automatically matched labels for places in Travel Portal. Activate and apply this skill when the user asks what labels a place has (e.g. "what labels does Prague have?", "show me the labels for Angkor Wat"). Place labels are derived from highlight similarity and cannot be manually assigned or removed.'

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
            - keywords (list): Keywords used to match the label to place highlights.
        """
        place = self.core_client.get_place(place_id=place_id)
        self._verify_error_response(place)

        return [self._extract_label(label) for label in place.get("labels", [])]


class LabelEditTools(BaseLabelTools):
    def _get_description(self) -> str | None:
        return "Provides capabilities to create and update standalone labels in Travel Portal. Activate and apply this skill when the user asks to manage a label or its keywords. Labels are automatically matched to place highlights; they cannot be manually assigned to or removed from individual places."

    def _get_supported_skill(self) -> str:
        return "label.edit"

    def _get_tools(self) -> list[Any]:
        return [
            self.create_label,
            self.update_label_keywords,
        ]

    def create_label(
        self,
        name: str,
        keywords: list[str] | None = None,
    ) -> dict[str, Any]:
        """Creates a standalone label, optionally with keywords.
        Use this when the user asks to create a label (e.g. "create a label called City").
        The keywords are used to match the label to place highlights. Labels are matched automatically and cannot be manually assigned to places.

        Args:
            name: Name of the label to create (e.g. "City").
            keywords: Optional list of keywords used to match the label to place highlights (e.g. `["city", "urban area", "dense buildings", "public transport"]`).

        Returns:
            The label object containing:
            - id (string): Unique label identifier.
            - name (string): Label name (e.g. `"City"`).
            - keywords (list): Keywords used to match the label to place highlights.
        """
        label = self.core_client.create_label(name=name, keywords=keywords)
        self._verify_error_response(label)

        return self._extract_label(label)

    def update_label_keywords(
        self, label_id: str, keywords: list[str]
    ) -> dict[str, Any]:
        """Replaces the keywords of an existing standalone label.
        Use this when the user asks to change the keywords that control matching to place highlights.
        The provided list fully replaces the existing keywords, so include the keywords that should be kept.
        Always resolve the label ID using get_labels. Never ask the user for a UUID.

        Args:
            label_id: The `id` of the label as returned by get_labels.
            keywords: New list of keywords used to match the label to place highlights.

        Returns:
            The label object containing:
            - id (string): Unique label identifier.
            - name (string): Label name (e.g. `"City"`).
            - keywords (list): Keywords used to match the label to place highlights.
        """
        updated_label = self.core_client.update_label(
            label_id=label_id, keywords=keywords
        )
        self._verify_error_response(updated_label)
        return self._extract_label(updated_label)
