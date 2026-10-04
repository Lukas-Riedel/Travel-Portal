# Note Skill

## Purpose
Provides capabilities to create and manage travel notes associated with specific trips in Travel Portal.

## Note Creation Guidelines
1. **Target Trip**: A valid `trip_id` is required. If not known, retrieve trips first using `get_trips`. Never create notes for past trips unless explicitly confimed by the user.
2. **Content Formatting**:
   - The note `content` must be well-structured in **Markdown**.
   - Use headings (`#`, `##`), bullet points, bold text, and numbered lists where appropriate.
   - For travel recommendations or itineraries, structure by categories or days (e.g., Highlights, Food & Dining, Tips).
   - Match the language requested or used by the user in the prompt.
3. **Execution**:
   - Call `create_trip_note(trip_id=trip_id, content=content)`.
4. **Result Reporting**:
   - Confirm to the user that the note was created for the specified trip with a short summary.
