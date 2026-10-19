# Airport Read Skill

## Purpose & Activation
Provides capabilities to query and view airports stored in Travel Portal.
Activate and apply this skill when the user asks about airports, their names, IATA codes, countries, or timezones (e.g. "what airport have I visited?", "show me all airports", "what is the full name of PRG?", "which country is DXB in?").

## Airport Guidelines & Actions
1. **Listing Airports**
   - When the user asks about airports, their names, codes, or locations, call `get_airports`.
   - Present the results clearly: full name, country, and IATA code (e.g. "Václav Havel Prague Airport, Czechia (PRG)").
   - If the user asks about a specific airport by code or name, call `get_airports` and filter the result - **NEVER ask the user for a UUID.**
2. **Extracting Airport ID**
   - Once the target airport is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**
