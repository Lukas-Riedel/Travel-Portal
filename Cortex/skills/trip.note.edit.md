# Trip Note Edit Skill

## Purpose & Activation
Provides capabilities to create, update, and delete notes, memos, and reminders for trips in Travel Portal.
Activate and apply this skill when the user asks to add, record, modify, edit, or delete a note for a trip (e.g. "add a note to my Japan trip", "update the note about train tickets", "delete the note about car rental").

## Note Guidelines & Actions
1. **Creating a Trip Note**
   - When the user asks to add, save, or record a note for a trip, call `create_trip_note`.
   - Always resolve the trip ID first using trip tools. **NEVER ask the user for a trip ID or UUID.**
   - The note content can contain Markdown formatting (e.g. bold, lists, links). Ensure the content accurately reflects what the user specified.
   - After creating the note, confirm with a concise summary stating the trip and the note content.
2. **Updating a Trip Note**
   - When the user asks to modify, rewrite, or update an existing trip note, call `update_trip_note`.
   - Always resolve the trip ID first using trip tools, then inspect existing notes using `get_notes_for_trip` to find the target note and its `id`. **NEVER ask the user for a UUID and NEVER invent one.**
   - If multiple notes match the user's description, **ASK THE USER** to clarify which note to update.
   - The `content` must be the complete updated Markdown text (not a diff or partial update).
   - After updating the note, confirm with a concise summary stating the trip and the updated content.
3. **Deleting a Trip Note**
   - When the user asks to delete, remove, or discard a note from a trip, call `delete_trip_note`.
   - Always resolve the trip ID first using trip tools, then inspect existing notes using `get_notes_for_trip` to find the target note and its `id`. **NEVER ask the user for a UUID and NEVER invent one.**
   - If multiple notes match the user's description, **ASK THE USER** to clarify which note to delete.
   - After deleting the note, confirm with a concise confirmation message.
