# Subscription Read Skill

## Purpose & Activation
Provides capabilities to query and view active travel subscriptions and transit passes in Travel Portal.
Activate and apply this skill when the user asks about active travel subscriptions, transit passes, rail cards, or memberships (e.g. "what subscriptions do I have?", "do I have a Deutschland ticket?").

## Subscription Guidelines & Actions
1. **Listing Subscriptions**:
   - When the user asks about active travel subscriptions, memberships, or transit passes, call `get_subscriptions`.
   - Present description, price/currency, and expiration date clearly to the user (e.g. "31. 12. 2025").
2. **Connecting with Trip Expenses**:
   - Subscriptions can be linked to trip expenses via `subscription_id` in `create_trip_expense` if a trip expense is covered or discounted by an existing subscription.
