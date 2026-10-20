# Airport Read Skill

## Purpose & Activation
Provides capabilities to query and view airports stored in Travel Portal.
Activate and apply this skill when the user asks about airports, their names, IATA codes, or countries (e.g. "what airport have I visited?", "show me all airports", "what is the full name of PRG?", "which country is DXB in?").

## Airport Guidelines & Actions
1. **Listing Airports**
   - When the user asks about airports, their names, codes, or locations, call `get_airports`.
   - If the user asks about a specific airport by code or name, call `get_airports` and filter the result. **NEVER ask the user for a UUID.**
   - The list of airports represent all airports physically visited, either on departure or arrival.
2. **Extracting Airport ID**
   - Once the target airport is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**
