from abc import ABC
from typing import Any, Final

from src.tools.base_tools import BaseCoreTools

DESCRIPTION_SAMPLE_LIMIT: Final[int] = 50


class BaseExpenseTools(BaseCoreTools, ABC):
    def _get_main_currency(self) -> str:
        config = self.core_client.get_configuration()
        return config["expensify"]["mainCurrency"]

    def _extract_expense(
        self,
        expense: dict[str, Any],
        trip: dict[str, Any],
        main_currency: str,
    ) -> dict[str, Any]:
        subscription = expense.get("subscription")

        return {
            "id": expense.get("id"),
            "trip_id": trip.get("id"),
            "trip_name": f"{trip.get('name')} {trip.get('year')}",
            "description": expense.get("description"),
            "type": expense.get("type"),
            "price": {
                "value": expense.get("value"),
                "currency": expense.get("currency"),
            },
            "converted_price": {
                "value": expense.get("mainCurrencyValue"),
                "currency": main_currency,
            },
            "subscription": subscription.get("description") if subscription else None,
        }


class TripExpenseReadTools(BaseExpenseTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to query and view expenses recorded for trips in Travel Portal. Activate and apply this skill when the user asks about their spending, costs, or expenses (e.g. "how much did I spend in Japan?", "what did I pay for hotels this year?", "list my flight expenses").'

    def _get_supported_skill(self) -> str:
        return "trip.expense.read"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_expenses,
            self.get_expense_total,
        ]

    def get_expenses(
        self,
        trip_id: str | None,
        year: int | None,
        expense_type: str | None,
    ) -> list[dict[str, Any]]:
        """Retrieves expenses from Travel Portal with optional filters.
        Use this when the user asks about their spending, costs, or expenses (e.g. "how much did I spend on my Japan trip?", "what did I pay for hotels this year?", "list all flight expenses").
        Returns an empty list if no expenses are recorded.

        Args:
            trip_id: Filter expenses to a specific trip. Resolve the trip ID first using trip tools. Never ask the user for a UUID.
                Pass None to retrieve expenses across all regular trips.
            year: Filter expenses to a specific calendar year (e.g. 2024). Pass None to retrieve expenses across all years.
                Ignored when trip_id is provided.
            expense_type: Filter expenses to a specific type. Must be one of: 'flight', 'transport', 'car', 'hotel', 'attraction', 'organizedTour', 'visa', 'other'.
                Pass None to retrieve expenses of all types.

        Returns:
            An empty list if no expenses exist, or a list of expense objects where each object contains:
            - id (string): Unique identifier of the expense. Use it when updating the expense.
            - trip_id (string): Unique identifier of the trip this expense belongs to.
            - trip_name (string): Name and year of the associated trip (e.g. `"Japan 2025"`).
            - description (string): Description of the expense (e.g. `"Hierapolis Archeological Site"`).
            - type (string): Type of the expense ('flight', 'transport', 'car', 'hotel', 'attraction', 'organizedTour', 'visa', 'other').
            - price (object): The amount actually paid, containing:
                - value (number): Amount in the currency it was paid in (e.g. `30`).
                - currency (string): 3-letter currency code it was paid in (e.g. `"EUR"`).
            - converted_price (object): The same amount converted to the user's main (home) currency using the exchange rate from the time the expense was created (or its currency last changed), not the current rate. Contains:
                - value (number): Converted amount (e.g. `756.3`). Converted values of different expenses are directly comparable and summable.
                - currency (string): 3-letter code of the main currency (e.g. `"CZK"`).
            - subscription (string | null): Description of the subscription the expense is tied to (e.g. `"Deutschland Ticket"`), or null.
        """
        if trip_id is not None:
            trip = self.core_client.get_trip(trip_id)
            if self._is_error_response(trip):
                return trip
            trips = [trip]
        else:
            trips = self.core_client.get_trips(
                trip_type="regular",
                include="expenses",
                year=year,
            )
            if self._is_error_response(trips):
                return trips

        main_currency = self._get_main_currency()
        return [
            self._extract_expense(
                expense=expense, trip=trip, main_currency=main_currency
            )
            for trip in trips
            if self._is_trip_visible(trip)
            for expense in trip.get("expenses", [])
            if expense_type is None or expense.get("type") == expense_type
        ]

    def get_expense_total(
        self,
        trip_id: str | None,
        year: int | None,
        expense_type: str | None,
    ) -> dict[str, Any]:
        """Calculates the total amount of expenses in the user's main (home) currency, with optional filters.
        Use this when the user asks how much they spent in total, or for a sum of expenses of a trip, a year or a type (e.g. "how much did I spend on my Japan trip?", "what is the total cost of flights this year?").
        Always use this tool for totals and never add up amounts yourself.

        Args:
            trip_id: Filter expenses to a specific trip. Resolve the trip ID first using trip tools. Never ask the user for a UUID.
                Pass None to sum expenses across all regular trips.
            year: Filter expenses to a specific calendar year (e.g. 2024). Pass None to sum expenses across all years.
                Ignored when trip_id is provided.
            expense_type: Filter expenses to a specific type. Must be one of: 'flight', 'transport', 'car', 'hotel', 'attraction', 'organizedTour', 'visa', 'other'.
                Pass None to sum expenses of all types.

        Returns:
            An object containing:
            - total (object): The sum of all matching expenses converted to the user's main (home) currency using the exchange rate from the time each expense was created (or its currency last changed), not the current rate. Contains:
                - value (number): Total amount (e.g. `27515.5`). It is `0` if no expense matches.
                - currency (string): 3-letter code of the main currency (e.g. `"CZK"`).
            - count (integer): Number of expenses included in the total.
        """
        expenses = self.get_expenses(
            trip_id=trip_id, year=year, expense_type=expense_type
        )
        if self._is_error_response(expenses):
            return expenses

        return {
            "total": {
                "value": round(
                    sum(e["converted_price"]["value"] or 0 for e in expenses), 2
                ),
                "currency": self._get_main_currency(),
            },
            "count": len(expenses),
        }


class TripExpenseEditTools(BaseExpenseTools):
    def _get_description(self) -> str | None:
        return 'Provides capabilities to create and update expenses for trips in Travel Portal. Activate and apply this skill when the user asks to add, record, modify, or correct an expense for a trip (e.g. "add 30 EUR for the museum tickets to my Turkey trip", "I paid 120 CZK for the bus", "change the hotel expense to 250 USD", "rename the taxi expense").'

    def _get_supported_skill(self) -> str:
        return "trip.expense.edit"

    def _get_tools(self) -> list[Any]:
        return [
            self.get_expense_descriptions,
            self.create_trip_expense,
            self.update_trip_expense,
        ]

    def get_expense_descriptions(self, expense_type: str) -> list[str]:
        """Retrieves descriptions of the most recent expenses of a given type across all regular trips in Travel Portal, newest first.
        Use this before creating an expense, to see how the user usually names expenses of that type.

        Args:
            expense_type: The type of the expenses to retrieve descriptions for. Must be one of: 'flight', 'transport', 'car', 'hotel', 'attraction', 'organizedTour', 'visa', 'other'.

        Returns:
            A list of at most 50 expense descriptions (e.g. `["Hierapolis Archeological Site", "Louvre tickets"]`), or an empty list if there is no expense of the given type.
        """
        trips = self.core_client.get_trips(
            trip_type="regular",
            include="expenses",
            sort="-oldest",
        )
        if self._is_error_response(trips):
            return trips

        descriptions = []
        for trip in trips:
            if not self._is_trip_visible(trip):
                continue

            for expense in reversed(trip.get("expenses", [])):
                if expense.get("type") != expense_type or not expense.get(
                    "description"
                ):
                    continue
                descriptions.append(expense["description"])
                if len(descriptions) >= DESCRIPTION_SAMPLE_LIMIT:
                    return descriptions

        return descriptions

    def create_trip_expense(
        self,
        trip_id: str,
        description: str,
        value: float,
        currency: str,
        expense_type: str,
        subscription_id: str | None,
    ) -> dict[str, Any]:
        """Creates a new expense for a specific trip in Travel Portal.
        Use this when the user asks to add, create, or record an expense, payment, or cost for a trip.
        Always resolve the trip ID first using trip tools. Never ask the user for a UUID.

        Args:
            trip_id: The unique identifier of the trip the expense belongs to. Resolve via trip tools.
            description: Short description of the expense (e.g. "Hierapolis Archeological Site"). Derive it from the descriptions returned by get_expense_descriptions.
            value: The value of the expense in the given currency (e.g. 30).
            currency: 3-letter currency code (e.g. "EUR", "USD", "CZK").
            expense_type: The type of the expense. Must be one of: 'flight', 'transport', 'car', 'hotel', 'attraction', 'organizedTour', 'visa', 'other'.
            subscription_id: Optional identifier of the subscription the expense is tied to. Pass None if not applicable.

        Returns:
            The created expense object containing:
            - id (string): Unique identifier of the expense.
            - trip_id (string): Unique identifier of the trip.
            - trip_name (string): Name and year of the associated trip.
            - description (string): Description of the expense.
            - type (string): Type of the expense.
            - price (object): The amount actually paid, containing:
                - value (number): Amount in the currency it was paid in (e.g. `30`).
                - currency (string): 3-letter currency code it was paid in (e.g. `"EUR"`).
            - converted_price (object): The same amount converted to the user's main (home) currency using the exchange rate from the time the expense was created (or its currency last changed), not the current rate. Contains:
                - value (number): Converted amount (e.g. `756.3`). Converted values of different expenses are directly comparable and summable.
                - currency (string): 3-letter code of the main currency (e.g. `"CZK"`).
            - subscription (string | null): Description of the subscription the expense is tied to (e.g. `"Deutschland Ticket"`), or null.
        """
        trip = self.core_client.get_trip(trip_id)
        if self._is_error_response(trip):
            return trip

        expense = self.core_client.create_trip_expense(
            trip_id=trip_id,
            description=description,
            value=value,
            currency=currency.upper(),
            expense_type=expense_type,
            subscription_id=subscription_id,
        )
        if self._is_error_response(expense):
            return expense

        return self._extract_expense(
            expense=expense, trip=trip, main_currency=self._get_main_currency()
        )

    def update_trip_expense(
        self,
        trip_id: str,
        expense_id: str,
        description: str | None,
        value: float | None,
        currency: str | None,
    ) -> dict[str, Any]:
        """Updates an existing expense in Travel Portal.
        Use this when the user asks to change the description, value, or currency of an existing expense.
        Always resolve the trip ID first using trip tools and find the expense ID via get_expenses. Never ask the user for a UUID.
        At least one of description, value, or currency must be provided.

        Args:
            trip_id: The unique identifier of the trip the expense belongs to. Resolve via trip tools.
            expense_id: The unique identifier of the expense to update. Resolve via get_expenses.
            description: New description for the expense. Pass None to leave unchanged.
            value: New value of the expense in the resulting currency. Pass None to leave unchanged.
            currency: New 3-letter currency code (e.g. "EUR", "USD", "CZK"). Pass None to leave unchanged.

        Returns:
            The updated expense object containing:
            - id (string): Unique identifier of the expense.
            - trip_id (string): Unique identifier of the trip.
            - trip_name (string): Name and year of the associated trip.
            - description (string): Description of the expense.
            - type (string): Type of the expense.
            - price (object): The amount actually paid, containing:
                - value (number): Amount in the currency it was paid in (e.g. `30`).
                - currency (string): 3-letter currency code it was paid in (e.g. `"EUR"`).
            - converted_price (object): The same amount converted to the user's main (home) currency using the exchange rate from the time the expense was created (or its currency last changed), not the current rate. Contains:
                - value (number): Converted amount (e.g. `756.3`). Converted values of different expenses are directly comparable and summable.
                - currency (string): 3-letter code of the main currency (e.g. `"CZK"`).
            - subscription (string | null): Description of the subscription the expense is tied to (e.g. `"Deutschland Ticket"`), or null.
        """
        trip = self.core_client.get_trip(trip_id)
        if self._is_error_response(trip):
            return trip

        expense = self.core_client.update_trip_expense(
            trip_id=trip_id,
            expense_id=expense_id,
            description=description,
            value=value,
            currency=currency.upper() if currency is not None else None,
        )
        if self._is_error_response(expense):
            return expense

        return self._extract_expense(
            expense=expense, trip=trip, main_currency=self._get_main_currency()
        )
