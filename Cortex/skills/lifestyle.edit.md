# Lifestyle Edit Skill

1. **Recording Food or Drink Intake**
   - When the user reports food or drink and wants it logged (e.g. "I had a chicken sandwich for lunch", "log my usual breakfast"), **MUST ALWAYS** call `get_recent_lifestyle_intake_events` before `record_lifestyle_intake`.
   - Review the returned events yourself. Decide whether any event describes the same or a sufficiently similar food or drink, names do not need to match exactly. For example, a history entry named "Chicken sandwich" may correspond to the user's "grilled chicken baguette". If an event corresponds, use its recorded calories for `calories`. Otherwise, estimate calories from the user's description. The helper returns history only, it does not choose a match or estimate calories.
   - Choose a concise `name` name that follows the user's usual naming style from the examples while accurately describing the reported item. Use the user's own specific wording when it identifies the food or drink.
   - For `timestamp`, use `convert_datetime_to_epoch` with the user's current timezone from Environmental Context as `tz_name`. If time of the intake is not provided, pass None. This will record the intake with the current timestamp automatically.
2. **Recording Physical Activity**
   - When the user reports a physical activity and wants it logged (e.g. "I went for a run", "log my 30-minute bike ride"), call `record_lifestyle_activity` directly.
   - Describe the activity concisely based on the user's wording. Use calories supplied by the user when available; otherwise make a reasonable calorie expenditure estimate and pass it to the tool.
3. **Deleting Lifestyle Event**
   - When the user wants to remove, delete, or discard a lifestyle event (such as calories intake or activity), call `delete_lifestyle_event`.
   - Always resolve the lifestyle event ID first, typically from the event object returned by the relevant create function when the user wants to delete an event they just created. **NEVER ask the user for a lifestyle event ID or UUID and NEVER invent one.**
   - After deleting the lifestyle event, confirm with a concise confirmation message.
4. **Renaming Lifestyle Event**
   - When the user wants to rename or correct the name/description of a recorded food, drink, or physical activity event, call `update_lifestyle_event_content` with the existing event ID and the new concise `name`.
   - Always resolve the lifestyle event ID first from the event object returned by the relevant create function or from lifestyle event history. **NEVER ask the user for a lifestyle event ID or UUID and NEVER invent one.**
   - After renaming the lifestyle event, confirm the new name concisely.
