# Airline Read Skill

1. **Listing Airlines**
   - When the user asks about airlines, their names, or codes, call `get_airlines`.
   - If the user asks about a specific airline by name or code, call `get_airlines` and filter the result. **NEVER ask the user for an airline ID or UUID.**
   - An airline with codes has at least one logged flight. An airline without codes has no logged flights yet, but one is likely already planned.
2. **Extracting Airline ID**
   - Once the target airline is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**
