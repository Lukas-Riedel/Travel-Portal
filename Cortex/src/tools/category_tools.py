from abc import ABC
from typing import Any

from src.tools.base_tools import BaseCoreTools


class BaseCategoryTools(BaseCoreTools, ABC):
    def _extract_category(self, category: dict[str, Any]) -> dict[str, Any]:
        metadata = category.get("metadata") or {}
        return {
            "id": category.get("id"),
            "name": category.get("name"),
            "type": category.get("category"),
            "color": metadata.get("color"),
        }


class CategoryReadTools(BaseCategoryTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to list and find categories in Travel Portal. Activate and apply this skill when the user asks about categories, their types, or a category by name (e.g. "show me all island categories", "list administrative regions in Czechia", "find the category Europe").'

    def _is_enabled_by_default(self) -> bool:
        return False

    def _get_supported_skill(self) -> str:
        return "category.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_categories,
            self.get_category_by_name,
        ]

    def get_categories(
        self,
        category_type: str | None,
        country: str | None,
    ) -> list[dict[str, Any]]:
        """Retrieves categories, optionally filtered by type or country.
        Use this when the user asks about categories - for example "show me all island categories" or "list administrative regions in Czechia".

        Args:
            category_type: Filter the results to one category type: continent, country, administrative, ocean, sea, bay, island, or region. Pass None for all types.
            country: Filter administrative categories to a country (e.g. "Czechia"). Pass None for categories without a country filter.

        Returns:
            A list of category objects, where each object contains:
            - id (string): Unique category identifier.
            - name (string): Category name (e.g. `"Europe"`).
            - type (string): Category type: continent, country, administrative, ocean, sea, bay, island, or region (e.g. `"continent"`).
            - color (string | null): Category color (e.g. `"#012169"`).
        """
        categories = self.core_client.get_categories(
            country=country,
            categories="administrative" if country is not None else category_type,
        )
        if self._is_error_response(categories):
            return categories

        return [self._extract_category(category) for category in categories]

    def get_category_by_name(self, query: str) -> dict[str, Any] | None:
        """Finds a category by searching the Travel Portal full-text search index.
        Use this when the user refers to a category by name - for example "find the category Europe" or "look up the United Kingdom category".

        Args:
            query: The category name or key words to search for (e.g. "Europe" or "United Kingdom"). Do not pass a full sentence.

        Returns:
            Null if no matching category exists, or a category object containing:
            - id (string): Unique category identifier.
            - name (string): Category name (e.g. `"Europe"`).
            - type (string): Category type: continent, country, administrative, ocean, sea, bay, island, or region (e.g. `"continent"`).
            - color (string | null): Category color (e.g. `"#012169"`).
        """
        search_results = self.core_client.search(
            query=query,
            include="category",
            limit=1,
        )
        if self._is_error_response(search_results):
            return search_results

        category_result = next(
            (result for result in search_results if result.get("type") == "category"),
            None,
        )
        if category_result is None:
            return None

        category_id = (category_result.get("entity") or {}).get("id")
        if not category_id:
            return None

        category = self.core_client.get_category(category_id)
        if self._is_error_response(category):
            return category

        return self._extract_category(category)


class PlaceCategoryReadTools(BaseCategoryTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to query categories associated with places in Travel Portal. Activate and apply this skill when the user asks which categories or regions a specific place belongs to (e.g. "which regions does Prague belong to?", "what categories are associated with Angkor Wat?").'

    def _is_enabled_by_default(self) -> bool:
        return True

    def _get_supported_skill(self) -> str:
        return "place.category.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_categories_for_place,
        ]

    def get_categories_for_place(self, place_id: str) -> list[dict[str, Any]]:
        """Retrieves the categories associated with a specific place.
        Use this when the user asks which categories or regions a place belongs to. Resolve the place first using place tools. Never ask the user for a UUID.

        Args:
            place_id: The unique identifier of the place, resolved using place tools.

        Returns:
            A list of category objects, where each object contains:
            - id (string): Unique category identifier.
            - name (string): Category name (e.g. `"Europe"`).
            - type (string): Category type: continent, country, administrative, ocean, sea, bay, island, or region (e.g. `"continent"`).
            - color (string | null): Category color (e.g. `"#012169"`).
        """
        place = self.core_client.get_place(place_id)
        if self._is_error_response(place):
            return place

        categories = []
        for category_identifier in place.get("categories", []):
            category = self.core_client.get_category(category_identifier.get("id"))
            if self._is_error_response(category):
                return category

            categories.append(self._extract_category(category))

        return categories


class CategoryEditTools(BaseCategoryTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to update category colors and public-holiday calendar URLs in Travel Portal. Activate and apply this skill when the user asks to change a category\'s color or public holidays calendar (e.g. "set the color of Europe to #012169", "set the Czech Republic public holidays calendar to URL").'

    def _is_enabled_by_default(self) -> bool:
        return False

    def _get_supported_skill(self) -> str:
        return "category.edit"

    def _get_tools(self) -> list[Any]:
        return [
            self.update_category_color,
            self.update_category_public_holidays_calendar,
        ]

    def update_category_color(self, category_id: str, color: str) -> dict[str, Any]:
        """Updates the color of an existing category.
        Use this when the user asks to set or change a category's color - for example "set the color of Europe to #012169". Resolve the category first using category tools. Never ask the user for a UUID.

        Args:
            category_id: The `id` of the category as returned by category tools.
            color: The new category color (e.g. `"#012169"`).

        Returns:
            The updated category object containing:
            - id (string): Unique category identifier.
            - name (string): Category name (e.g. `"Europe"`).
            - type (string): Category type: continent, country, administrative, ocean, sea, bay, island, or region (e.g. `"continent"`).
            - color (string | null): Category color (e.g. `"#012169"`).
        """
        category = self.core_client.update_category(
            category_id=category_id,
            color=color,
        )
        if self._is_error_response(category):
            return category

        return self._extract_category(category)

    def update_category_public_holidays_calendar(
        self, category_id: str, calendar_url: str
    ) -> dict[str, Any]:
        """Updates the public holidays calendar URL of an existing category.
        Use this when the user asks to set or change a category's public holidays calendar - for example "update the Czech Republic public holidays calendar". Resolve the category first using category tools. Never ask the user for a UUID.

        Args:
            category_id: The `id` of the category as returned by category tools.
            calendar_url: The new public holidays calendar URL (e.g. `"https://calendar.google.com/calendar/ical/en.uk%23holiday%40group.v.calendar.google.com/public/basic.ics"`).

        Returns:
            The updated category object containing:
            - id (string): Unique category identifier.
            - name (string): Category name (e.g. `"Europe"`).
            - type (string): Category type: continent, country, administrative, ocean, sea, bay, island, or region (e.g. `"continent"`).
            - color (string | null): Category color (e.g. `"#012169"`).
        """
        category = self.core_client.update_category(
            category_id=category_id,
            public_holidays_calendar=calendar_url,
        )
        if self._is_error_response(category):
            return category

        return self._extract_category(category)
