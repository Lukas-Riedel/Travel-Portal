# Label Edit Skill

1. **Creating Label**
   - Create a label only when the user clearly asks for one. If the requested name or intent is ambiguous, ask for clarification.
   - Call `create_label` with the requested name and, when provided, its description. The description is used to match the label to place highlights.
   - After creation, confirm the label name and description.
2. **Updating Label Description**
   - Call `get_labels` and resolve the target label ID from the returned list. If the target is unclear, ask which label they mean. **NEVER ask the user for a label ID or UUID.**
   - Call `update_label_description` with the label ID and new description.
   - After updating, confirm with a concise summary stating the label name, and new description.
