# Airport Read Skill

1. **Listing Airports**
   - When the user asks about airports, their names, codes, or locations, call `get_airports`.
   - If the user asks about a specific airport by code or name, call `get_airports` and filter the result. **NEVER ask the user for an airport ID or UUID.**
   - The list of airports represent all airports physically visited, either on departure or arrival.
2. **Extracting Airport ID**
   - Once the target airport is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**
