from abc import ABC
from typing import Any

from src.tools.base_tools import BaseCoreTools


class BaseStatisticsTools(BaseCoreTools, ABC):
    def _extract_statistics(
        self, statistics: list[dict[str, Any]]
    ) -> list[dict[str, Any]]:
        return [
            {
                "name": statistic.get("name"),
                "value": statistic.get("value"),
                "unit": statistic.get("unit"),
            }
            for statistic in statistics
        ]


class TripStatisticsReadTools(BaseStatisticsTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to query statistics and discover interesting facts about trips in Travel Portal. Activate and apply this skill when the user asks what is known about a specific trip, asks for an interesting fact, or asks a general open-ended question about a trip (e.g. "what do you know about my Japan trip?", "tell me an interesting fact about Iceland", "what stands out about my trip to Peru?").'

    def _get_supported_skill(self) -> str:
        return "trip.statistics.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_trip_statistics,
        ]

    def get_trip_statistics(
        self, trip_id: str
    ) -> list[dict[str, Any]] | dict[str, Any]:
        """Retrieves the statistics available for a specific trip.
        Use this when the user asks what is known about a trip, requests an interesting fact, or asks a general open-ended question about a specific trip. Resolve the trip ID first using trip tools. Never ask the user for a UUID.

        Args:
            trip_id: The unique identifier of the trip whose statistics should be retrieved.

        Returns:
            A list of available trip statistics, where each object contains:
            - name (string): The statistic's technical name (e.g. `"TOTAL_VISITED_PLACES_COUNT"`).
            - value (string | number | boolean | list): The statistic value. Lists contain structured key-value records when applicable (e.g. `[{"key": "Japan", "value": 12}]`).
            - unit (string): The unit associated with the statistic (e.g. `"kilometers"`).
        """
        trip = self.core_client.get_trip(trip_id=trip_id)
        self._verify_error_response(trip)

        return self._extract_topic(
            topic_ids=[trip_id],
            data=self._extract_statistics(statistics=trip.get("statistics", [])),
        )


class CategoryStatisticsReadTools(BaseStatisticsTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to query statistics and discover interesting facts about categories in Travel Portal. Activate and apply this skill when the user asks what is known about a specific category, asks for an interesting fact, or asks a general open-ended question about a category (e.g. "what do you know about Japan?", "tell me an interesting fact about California", "what stands out about the Balkans?").'

    def _get_supported_skill(self) -> str:
        return "category.statistics.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_category_statistics,
        ]

    def get_category_statistics(
        self, category_id: str
    ) -> list[dict[str, Any]] | dict[str, Any]:
        """Retrieves the available statistics for a specific category.
        Use this when the user asks for statistics, interesting facts, or an overview about a specific region or category. Resolve the category ID first using category tools. Never ask the user for a UUID.

        Args:
            category_id: The unique identifier of the category whose statistics should be retrieved.

        Returns:
            A list of available category statistics, where each object contains:
            - name (string): The statistic's technical name (e.g. `"TOTAL_VISITED_PLACES_COUNT"`).
            - value (string | number | boolean | list): The statistic value. Lists contain structured key-value records when applicable (e.g. `[{"key": "Japan", "value": 12}]`).
            - unit (string): The unit associated with the statistic (e.g. `"kilometers"`).
        """
        category = self.core_client.get_category(category_id=category_id)
        self._verify_error_response(category)

        return self._extract_topic(
            topic_ids=[category_id],
            data=self._extract_statistics(statistics=category.get("statistics", [])),
        )


class YearStatisticsReadTools(BaseStatisticsTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to query statistics and discover interesting facts about a calendar year in Travel Portal. Activate and apply this skill when the user asks what is known about a specific year, asks for an interesting fact, or asks a general open-ended question about a year (e.g. "what do you know about my travels in 2024?", "tell me an interesting fact about 2023", "what stands out about my travels last year?").'

    def _get_supported_skill(self) -> str:
        return "year.statistics.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_year_statistics,
        ]

    def get_year_statistics(self, year: int) -> list[dict[str, Any]] | dict[str, Any]:
        """Retrieves the available statistics for a specific calendar year.
        Use this when the user asks for statistics, interesting facts, or an overview about a specific year.

        Args:
            year: The calendar year whose statistics should be retrieved (e.g. 2024).

        Returns:
            A list of available year statistics, where each object contains:
            - name (string): The statistic's technical name (e.g. `"TOTAL_VISITED_PLACES_COUNT"`).
            - value (string | number | boolean | list): The statistic value. Lists contain structured key-value records when applicable (e.g. `[{"key": "Japan", "value": 12}]`).
            - unit (string): The unit associated with the statistic (e.g. `"kilometers"`).
        """
        year_data = self.core_client.get_year(year=year)
        self._verify_error_response(year_data)

        return self._extract_topic(
            topic_ids=[str(year)],
            data=self._extract_statistics(statistics=year_data.get("statistics", [])),
        )


class StatisticsReadTools(BaseStatisticsTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to query statistics and discover interesting facts about the user\'s travel data overall. Activate and apply this skill when the user asks what is known about their travels overall, asks for an interesting fact, or asks a general open-ended question about their travel history (e.g. "what do you know about my travels overall?", "tell me an interesting fact about my trips", "what stands out about my travel history?").'

    def _get_supported_skill(self) -> str:
        return "statistics.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_statistics,
        ]

    def get_statistics(self) -> list[dict[str, Any]] | dict[str, Any]:
        """Retrieves the available overall statistics across the user's travel data.
        Use this for broad questions about travel statistics or when the user asks what overall statistics are available. Use trip, category, or year statistics tools when the request is limited to a specific trip, category, or year.

        Returns:
            A list of available overall statistics, where each object contains:
            - name (string): The statistic's technical name (e.g. `"TOTAL_VISITED_PLACES_COUNT"`).
            - value (string | number | boolean | list): The statistic value. Lists contain structured key-value records when applicable (e.g. `[{"key": "Japan", "value": 12}]`).
            - unit (string): The unit associated with the statistic (e.g. `"kilometers"`).
        """
        statistics = self.core_client.get_statistics()
        self._verify_error_response(statistics)

        return self._extract_statistics(statistics=statistics)
