# Place Note Read Skill

1. **Listing Notes for Place**
   - When the user asks about notes or observations on a particular place (e.g. "what notes do I have for Prague?", "show me the notes for Eiffel Tower"), call `get_notes_for_place`.
   - Always resolve the place ID first using place tools. **NEVER ask the user for a place ID or UUID.**
   - **Place objects returned by place tools do NOT contain note data.** You **MUST ALWAYS** call `get_notes_for_place` explicitly - never conclude that no notes exist based on the place object alone.
   - Present note contents clearly, formatting any Markdown appropriately, along with the creation date if relevant.
2. **Extracting Note ID**
   - Once the target note is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**
