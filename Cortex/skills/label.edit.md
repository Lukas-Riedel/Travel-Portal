# Label Edit Skill

1. **Creating Label**
   - When the user asks to create or add a label, call `create_label` with the provided `name`.
   - If the requested name or intent is ambiguous, **ASK THE USER** before calling `create_label`. **NEVER invent a name the user did not provide.**
   - A new label has no places until the user picks its example photos in Travel Portal. Places are matched to the label by the similarity of their photos to those example photos. The example photos cannot be chosen through this tool, so tell the user to pick them in Travel Portal.
   - After creating, confirm with a concise summary stating the label name (e.g. "The label City has been created.") and remind the user that example photos still have to be chosen.
   - Only creating a label is supported. Existing labels cannot be changed or renamed with this tool.
