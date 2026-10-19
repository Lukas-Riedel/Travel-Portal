# Airport Edit Skill

## Purpose & Activation
Provides capabilities to rename airports in Travel Portal.
Activate and apply this skill when the user asks to rename an airport or correct its full official name (e.g. "rename PRG", "fix the full name of Dubai airport", "set the long name of JFK").

## Airport Edit Guidelines & Actions
1. **Renaming an Airport**
   - When the user asks to rename an airport or update its full name, first call `get_airports` to find the airport and obtain its `id`. **NEVER ask the user for a UUID.**
   - Then call `update_airport_name` with the resolved `airport_id` and the new `name`.
   - Only the full official name can be changed - IATA code, country, and timezone cannot be edited.
   - After renaming, confirm with a concise summary (e.g. "PRG has been renamed to 'Václav Havel Prague Airport'.").
