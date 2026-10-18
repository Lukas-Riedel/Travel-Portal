# Trip Note Read Skill

## Purpose & Activation
Provides capabilities to query and inspect notes, memos, reminders, and observations recorded for trips in Travel Portal.
Activate and apply this skill when the user asks about trip notes, memos, or information written down for a specific trip or across all trips (e.g. "what notes did I take during my Japan trip?", "show me notes for Egypt", "what did I note down this year?").

## Note Guidelines & Actions
1. **Listing Notes for a Specific Trip**
   - When the user asks about notes or observations on a particular trip (e.g. "what notes do I have for Japan?", "show me the notes for my upcoming trip"), call `get_notes_for_trip`.
   - Always resolve the trip ID first using trip tools. **Never ask the user for a UUID.**
   - **Trip objects returned by trip tools do NOT contain note data.** You **MUST ALWAYS** call `get_notes_for_trip` explicitly - never conclude that no notes exist based on the trip object alone.
   - Present note contents clearly, formatting any Markdown appropriately, along with the creation date if relevant.
2. **Extracting Note ID**
   - Once the target note is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**