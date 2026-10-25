# Trip Note Read Skill

1. **Listing Notes for Trip**
   - When the user asks about notes or observations on a particular trip (e.g. "what notes do I have for Japan?", "show me the notes for my upcoming trip"), call `get_notes_for_trip`.
   - Always resolve the trip ID first using trip tools. **NEVER ask the user for a trip ID or UUID.**
   - **Trip objects returned by trip tools do NOT contain note data.** You **MUST ALWAYS** call `get_notes_for_trip` explicitly - never conclude that no notes exist based on the trip object alone.
   - Present note contents clearly, formatting any Markdown appropriately, along with the creation date if relevant.
2. **Extracting Note ID**
   - Once the target note is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**