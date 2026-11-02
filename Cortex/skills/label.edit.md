# Label Edit Skill

1. **Creating Label**
   - When the user asks to create or add a label, call `create_label` with the provided `name`.
   - Provide `keywords` as a list of strings when the user mentions them (e.g. "with keywords beach, sea, palm trees" becomes `["beach", "sea", "palm trees"]`). The keywords are used to automatically match the label to place highlights. If the user gives none, pass None.
   - If the requested name or intent is ambiguous, **ASK THE USER** before calling `create_label`. **NEVER invent a name or keywords the user did not provide.**
   - After creating, confirm with a concise summary stating the label name and keywords (e.g. "The label City has been created with keywords: city, urban area.").
2. **Updating Label Keywords**
   - When the user asks to change, add, or remove the keywords of a label, first call `get_labels` to find the label and obtain its `id`. **NEVER ask the user for a label ID or UUID.**
   - If more labels match the user's description, **ASK THE USER** which one before updating.
   - `update_label_keywords` replaces the whole list. When the user asks to add or remove only some keywords, start from the current keywords returned by `get_labels` and pass the full resulting list.
   - Only the keywords can be changed. The label name cannot be edited.
   - After updating, confirm with a concise summary stating the label name and the new keywords.