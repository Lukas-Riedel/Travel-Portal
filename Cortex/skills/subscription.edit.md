# Subscription Edit Skill

## Purpose & Activation
Provides capabilities to record and create active travel subscriptions and transit passes in Travel Portal.
Activate and apply this skill when the user asks to save, register, or create a travel subscription or period transit pass.

## Subscription Guidelines & Actions
1. **Creating Subscriptions**
   - When the user asks to save or register a subscription, call `create_subscription`.
   - First, call `get_subscription_descriptions` to discover existing subscription descriptions and propose an appropriate name. If unsure, confirm with the user.
   - For `expiration`, use `convert_datetime_to_epoch` with the user's current timezone from Environmental Context as `tz_name`.
   - **NEVER create subscriptions with expiration in the past.**
   - After creating a subscription, confirm with a concise summary stating description, value, currency, and validity date.
