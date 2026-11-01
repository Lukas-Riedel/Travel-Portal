# Subscription Read Skill

1. **Listing Subscriptions**
   - When the user asks about active travel subscriptions, memberships, or transit passes, call `get_subscriptions`.
   - Present description, price/currency, and expiration date clearly to the user (e.g. "31. 12. 2025").
2. **Extracting Subscription ID**
   - Once the target subscription is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**
