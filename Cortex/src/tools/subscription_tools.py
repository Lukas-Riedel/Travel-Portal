from abc import ABC
from typing import Any

from src.tools.base_tools import BaseCoreTools


class BaseSubscriptionTools(BaseCoreTools, ABC):
    def _extract_subscription(
        self,
        subscription: dict[str, Any],
    ) -> dict[str, Any]:
        return {
            "id": subscription.get("id"),
            "description": subscription.get("description"),
            "value": subscription.get("value"),
            "currency": subscription.get("currency"),
            "expiration": self._epoch_to_iso(
                epoch=subscription.get("expiration"),
                user_tz=self._get_user_timezone(),
            ),
        }


class SubscriptionReadTools(BaseSubscriptionTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to query and view active travel subscriptions and transit passes in Travel Portal. Activate and apply this skill when the user asks about active travel subscriptions, transit passes, rail cards, or memberships (e.g. "what subscriptions do I have?", "do I have a Deutschland ticket?").'

    def _get_supported_skill(self) -> str:
        return "subscription.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_subscriptions,
            self.get_subscription_descriptions,
        ]

    def get_subscriptions(self) -> list[dict[str, Any]]:
        """Retrieves all active travel-related subscriptions in Travel Portal (e.g. public transport passes, rail cards, airline subscription clubs).
        Use this when the user asks about their active travel subscriptions or passes (e.g. "what subscriptions do I have?", "do I have a Deutschland ticket?").

        Returns:
            A list of active subscription objects, where each object contains:
            - id (string): Unique subscription identifier. You only need it to reference the subscription when creating a new expense.
            - description (string): Description/title of the subscription (e.g. `"Deutschland Ticket"`, `"Swiss Half Fare Pass"`).
            - value (number): Cost of the subscription.
            - currency (string): 3-letter currency code (e.g. `"EUR"`, `"USD"`, `"CZK"`).
            - expiration (string): Expiration date in ISO 8601 format with timezone offset (e.g. `"2025-12-31T23:59:59+0200"`).
        """
        subscriptions = self.core_client.get_subscriptions()
        self._verify_error_response(subscriptions)

        return [self._extract_subscription(s) for s in subscriptions]

    def get_subscription_descriptions(self) -> list[str]:
        """Retrieves descriptions of all active travel-related subscriptions in Travel Portal (e.g. public transport passes, rail cards, airline subscription clubs).

        Returns:
            A list of descriptions of active subscription objects.
        """
        subscriptions = self.core_client.get_subscriptions()
        self._verify_error_response(subscriptions)

        return [s.get("description") for s in subscriptions if s.get("description")]


class SubscriptionEditTools(BaseSubscriptionTools):
    def _get_description(self) -> str | None:
        return "Provides capabilities to record and create active travel subscriptions and transit passes in Travel Portal. Activate and apply this skill when the user asks to save, register, or create a travel subscription or period transit pass."

    def _get_supported_skill(self) -> str:
        return "subscription.edit"

    def _get_tools(self) -> list[Any]:
        return [
            self.create_subscription,
        ]

    def create_subscription(
        self,
        description: str,
        value: float,
        currency: str,
        expiration: int,
    ) -> dict[str, Any]:
        """Creates and stores a new recurring travel subscription / pass in Travel Portal.
        Use this when the user asks to save or register a travel subscription or period transit pass.

        Args:
            description: Description/name of the subscription (e.g. "Deutschland Ticket", "America the Beautiful Pass").
            value: The monetary cost or value of the subscription (e.g. 58.0).
            currency: 3-letter currency code (e.g. "EUR", "USD", "CZK", "GBP").
            expiration: Required expiration date as Unix epoch timestamp (seconds) until when the subscription is valid.

        Returns:
            The created subscription object containing:
            - id (string): Unique subscription identifier. You only need it to reference the subscription when creating a new expense.
            - description (string): Description/title of the subscription (e.g. `"Deutschland Ticket"`, `"Swiss Half Fare Pass"`).
            - value (number): Cost of the subscription.
            - currency (string): 3-letter currency code (e.g. `"EUR"`, `"USD"`, `"CZK"`).
            - expiration (string): Expiration date in ISO 8601 format with timezone offset (e.g. `"2025-12-31T23:59:59+0200"`).
        """
        subscription = self.core_client.create_subscription(
            description=description,
            value=value,
            currency=currency.upper(),
            expiration=expiration,
        )
        self._verify_error_response(subscription)

        return self._extract_subscription(subscription)
