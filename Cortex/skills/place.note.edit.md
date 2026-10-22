# Place Note Edit Skill

1. **Creating a Place Note**
   - When the user asks to add, save, or record a note for a place, call `create_place_note`.
   - Always resolve the place ID first using place tools. **NEVER ask the user for a place ID or UUID.**
   - The note content can contain Markdown formatting (e.g. bold, lists, links). Ensure the content accurately reflects what the user specified.
   - After creating the note, confirm with a concise summary stating the place and the note content.
2. **Updating a Place Note**
   - When the user asks to modify, rewrite, or update an existing place note, call `update_place_note`.
   - Always resolve the place ID first using place tools, then inspect existing notes using `get_notes_for_place` to find the target note and its `id`. **NEVER ask the user for a UUID and NEVER invent one.**
   - If multiple notes match the user's description, **ASK THE USER** to clarify which note to update.
   - The `content` must be the complete updated Markdown text (not a diff or partial update).
   - After updating the note, confirm with a concise summary stating the place and the updated content.
3. **Deleting a Place Note**
   - When the user asks to delete, remove, or discard a note from a place, call `delete_place_note`.
   - Always resolve the place ID first using place tools, then inspect existing notes using `get_notes_for_place` to find the target note and its `id`. **NEVER ask the user for a UUID and NEVER invent one.**
   - If multiple notes match the user's description, **ASK THE USER** to clarify which note to delete.
   - After deleting the note, confirm with a concise confirmation message.
