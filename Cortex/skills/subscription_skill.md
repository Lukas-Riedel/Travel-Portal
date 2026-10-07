# Subscription Skill

## Purpose
Provides capabilities to query, view, and record active travel subscriptions and transit passes in Travel Portal.

## Subscription Entity Structure
A subscription object contains:
- `id` (string): Unique identifier of the subscription. You only need it to reference the subscription when creating a new expense.
- `description` (string): Description/title of the subscription (e.g. `"Deutschland Ticket"`, `"Swiss Half Fare Card"`).
- `value` (number): Recurring cost or value of the subscription.
- `currency` (string): 3-letter currency code (e.g. `"EUR"`, `"USD"`, `"CZK"`).
- `expiration` (string): Expiration date in ISO 8601 format with timezone offset (e.g. `"2025-12-31T23:59:59+0200"`).

## Subscription Guidelines & Actions
1. **Listing Subscriptions**:
   - When the user asks about active travel subscriptions, memberships, or transit passes, call `get_subscriptions()`.
   - Present description, price/currency, and expiration date clearly to the user in their local timezone (e.g. "31. 12. 2025").
2. **Creating Subscriptions**:
   - When the user asks to save or register a subscription, do the following:
      - Call `get_subscription_descriptions()` to discover descriptions of already existing subscriptions, and propose a description for the subscription being created appropriately. If unsure, confirm with the user.
      - Call `create_subscription(description, value, currency, expiration)`:
          - `description`: Name or summary of the subscription.
          - `value`: Price of the subscription.
          - `currency`: 3-letter currency code (uppercase).
          - `expiration`: Expiration timestamp in ISO 8601 format with timezone offset (e.g. `"2025-12-31T23:59:59+0200"`).
   - **NEVER create subscriptions with expiration in the past.**
   - After creating a subscription, confirm with a concise summary stating description, value, currency, and validity date.
3. **Connecting with Trip Expenses**:
   - Subscriptions can be linked to trip expenses via `subscription_id` in `create_trip_expense` if a trip expense is covered or discounted by an existing subscription.
