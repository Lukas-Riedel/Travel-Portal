# Place Note Read Skill

## Purpose & Activation
Provides capabilities to query and inspect notes, memos, reminders, and observations recorded for places in Travel Portal.
Activate and apply this skill when the user asks about place notes, memos, or information written down for a specific place (e.g. "what notes do I have for Prague?", "show me notes for Angkor Wat", "did I write any tips for Eiffel Tower?").

## Note Guidelines & Actions
1. **Listing Notes for a Specific Place**
   - When the user asks about notes or observations on a particular place (e.g. "what notes do I have for Prague?", "show me the notes for Eiffel Tower"), call `get_notes_for_place`.
   - Always resolve the place ID first using place tools. **Never ask the user for a UUID.**
   - **Place objects returned by place tools do NOT contain note data.** You **MUST ALWAYS** call `get_notes_for_place` explicitly - never conclude that no notes exist based on the place object alone.
   - Present note contents clearly, formatting any Markdown appropriately, along with the creation date if relevant.
2. **Extracting Note ID**
   - Once the target note is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**
