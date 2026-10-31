# Trip Note Edit Skill

1. **Creating Trip Note**
   - When the user asks to add, save, or record a note for a trip, call `create_trip_note`.
   - Always resolve the trip ID first using the trip tools. **NEVER ask the user for a trip ID or UUID.**
   - Apply the Markdown formatting rules in section 4. If necessary, call `get_notes_for_trip` first to inspect the structure of the trip's existing notes.
   - After creating the note, confirm with a concise summary stating the trip and the note content.
2. **Updating Trip Note**
   - When the user asks to modify, rewrite, or update an existing trip note, call `update_trip_note_content`.
   - Always resolve the trip ID first using the trip tools, then inspect the existing notes with `get_notes_for_trip` to find the target note and its `id`. **NEVER ask the user for a UUID and NEVER invent one.**
   - If multiple notes match the user's description, **ASK THE USER** to clarify which note to update.
   - The `content` must contain the complete updated Markdown text, not a diff or a partial update. Apply the Markdown formatting rules in section 4.
   - After updating the note, confirm with a concise summary stating the trip and the updated content.
3. **Deleting Trip Note**
   - When the user asks to delete, remove, or discard a note from a trip, call `delete_trip_note`.
   - Always resolve the trip ID first using the trip tools, then inspect the existing notes with `get_notes_for_trip` to find the target note and its `id`. **NEVER ask the user for a UUID and NEVER invent one.**
   - If multiple notes match the user's description, **ASK THE USER** to clarify which note to delete.
   - After deleting the note, confirm with a concise confirmation message.
4. **Formatting Trip Notes**
   - The note content may include Markdown, such as bold text, lists, and links. Preserve the user's intended meaning and formatting.
   - **Follow the Markdown structure used by the trip's other notes.** Use `get_notes_for_trip` before creating or updating a note when you need to inspect that structure. In particular, use the top-level heading for the note topic; notes with the same topic are grouped together in the UI.
   - Unless the existing notes establish a different structure, use `#` for the main topic, `###` for a destination or itinerary subsection, bold text for section headings and key labels, unordered lists for options or facts, nested lists for details, and numbered lists for steps or stops whose order matters.
   - Keep headings and list items concise and consistent. Use the same formatting convention throughout the note.
