from datetime import datetime, timezone
from typing import Any

from src.service.core_api_client import CoreApiClient


class SubscriptionTools:
    def __init__(self, core_api_client: CoreApiClient):
        self.core_api_client = core_api_client

    def get_tools(self) -> list[Any]:
        return [
            self.get_subscription_descriptions,
            self.get_subscriptions,
            self.create_subscription,
        ]

    def get_subscription_descriptions(self) -> list[str]:
        """Retrieves descriptions of all active travel-related subscriptions in Travel Portal (e.g. public transport passes, rail cards, airline subscription clubs).

        Returns:
            A list of descriptions of active subscription objects.
        """
        subscriptions = self.get_subscriptions()
        if isinstance(subscriptions, dict) and subscriptions.get("message"):
            return subscriptions

        return [s.get("description") for s in subscriptions]

    def get_subscriptions(self) -> list[dict[str, Any]]:
        """Retrieves all active travel-related subscriptions in Travel Portal (e.g. public transport passes, rail cards, airline subscription clubs).
        Use this when the user asks about their active travel subscriptions or passes (e.g. "what subscriptions do I have?", "do I have a Deutschland ticket?").

        Returns:
            A list of active subscription objects, where each object contains:
            - id (string): Unique subscription identifier. You only need it to reference the subscription when creating a new expense.
            - description (string): Description/title of the subscription (e.g. 'Deutschland Ticket', 'Swiss Half Fare Pass').
            - value (number): Cost of the subscription.
            - currency (string): 3-letter currency code (e.g. 'EUR', 'USD', 'CZK').
            - expiration (string): Expiration date in ISO 8601 UTC format (e.g. '2025-12-31T23:59:59Z').
        """
        subscriptions = self.core_api_client.get_subscriptions()
        if isinstance(subscriptions, dict) and subscriptions.get("message"):
            return subscriptions
        
        return [self._extract_subscription(s) for s in subscriptions]

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
            - description (string): Description/title of the subscription (e.g. 'Deutschland Ticket', 'Swiss Half Fare Pass').
            - value (number): Cost of the subscription.
            - currency (string): 3-letter currency code (e.g. 'EUR', 'USD', 'CZK').
            - expiration (string): Expiration date in ISO 8601 UTC format (e.g. '2025-12-31T23:59:59Z').
        """
        subscription = self.core_api_client.create_subscription(
            description=description,
            value=value,
            currency=currency.upper(),
            expiration=expiration,
        )
        if isinstance(subscription, dict) and subscription.get("message"):
            return subscription
            
        return self._extract_subscription(subscription)

    def _extract_subscription(self, subscription: dict[str, Any]) -> dict[str, Any]:
        return {
            "id": subscription.get("id"),
            "description": subscription.get("description"),
            "value": subscription.get("value"),
            "currency": subscription.get("currency"),
            "expiration": self._epoch_to_iso(subscription.get("expiration"))
        }

    def _epoch_to_iso(self, epoch: int) -> str:
        return datetime.fromtimestamp(epoch, tz=timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")