# Label Read Skill

1. **Listing Labels**
   - When the user asks to browse or list available labels, call `get_labels`.
   - If the user asks about a specific label by name, call `get_labels` and filter the result. **NEVER ask the user for a label ID or UUID.**
   - Present each label by its name. **NEVER invent labels that are not returned by the tool.**
2. **Extracting Label ID**
   - Once the target label is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**
