# Airline Edit Skill

1. **Creating an Airline**
   - When the user asks to add or create an airline, call `create_airline` with the provided `name`.
   - After creating, confirm with a concise summary (e.g. "WizzAir has been added.").
2. **Assigning an IATA Code**
   - When the user asks to add or assign an IATA code to an airline, first call `get_airlines` to find the airline and obtain its `id`. **NEVER ask the user for an airline ID or UUID.**
   - Then call `assign_airline_code` with the resolved `airline_id` and the `code`.
   - A code can only be assigned if a flight with that code has already been logged. If the assignment fails, it is likely because no such flight exists yet.
   - After assigning, confirm with a concise summary (e.g. "Code W6 has been assigned to WizzAir.").

