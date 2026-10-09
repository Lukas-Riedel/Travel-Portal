# Subscription Skill

## Purpose
Provides capabilities to query, view, and record active travel subscriptions and transit passes in Travel Portal.

## Subscription Guidelines & Actions
1. **Listing Subscriptions**:
   - When the user asks about active travel subscriptions, memberships, or transit passes, call `get_subscriptions`.
   - Present description, price/currency, and expiration date clearly to the user (e.g. "31. 12. 2025").
2. **Creating Subscriptions**:
   - When the user asks to save or register a subscription, do the following:
     - Call `get_subscription_descriptions` to discover existing subscription descriptions and propose an appropriate name. If unsure, confirm with the user.
     - Call `create_subscription`. For `expiration`, use `convert_datetime_to_epoch` with the user's current timezone from Environmental Context as `tz_name`.
   - **NEVER create subscriptions with expiration in the past.**
   - After creating a subscription, confirm with a concise summary stating description, value, currency, and validity date.
3. **Connecting with Trip Expenses**:
   - Subscriptions can be linked to trip expenses via `subscription_id` in `create_trip_expense` if a trip expense is covered or discounted by an existing subscription.
