# Airline Edit Skill

## Purpose & Activation
Provides capabilities to create and update airlines in Travel Portal.
Activate and apply this skill when the user asks to add, rename, update the logo of, or assign a code to an airline (e.g. "add a new airline", "rename WizzAir", "assign code FR to Ryanair").

## Airline Edit Guidelines & Actions
1. **Creating an Airline**
   - When the user asks to add or create an airline, call `create_airline` with the provided `name`.
   - Optionally provide `logo` as an SVG string if the user supplies one.
   - After creating, confirm with a concise summary (e.g. "WizzAir has been added.").
2. **Renaming an Airline**
   - When the user asks to rename an airline or correct its name, first call `get_airlines` to find the airline and obtain its `id`. **NEVER ask the user for a UUID.**
   - Then call `update_airline_name` with the resolved `airline_id` and the new `name`.
   - After renaming, confirm with a concise summary (e.g. "WizzAir has been renamed to 'Wizz Air'.").
3. **Updating an Airline Logo**
   - When the user asks to set or replace the logo of an airline, first call `get_airlines` to find the airline and obtain its `id`. **NEVER ask the user for a UUID.**
   - Then call `update_airline_logo` with the resolved `airline_id` and the SVG `logo` string provided by the user.
   - After updating, confirm with a concise summary (e.g. "The logo for WizzAir has been updated.").
4. **Assigning an IATA Code**
   - When the user asks to add or assign an IATA code to an airline, first call `get_airlines` to find the airline and obtain its `id`. **NEVER ask the user for a UUID.**
   - Then call `assign_airline_code` with the resolved `airline_id` and the `code`.
   - A code can only be assigned if a flight with that code has already been logged. If the assignment fails, it is likely because no such flight exists yet.
   - After assigning, confirm with a concise summary (e.g. "Code W6 has been assigned to WizzAir.").

