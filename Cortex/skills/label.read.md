# Label Read Skill

## Purpose & Activation
Provides capabilities to query and inspect labels available in Travel Portal.
Activate and apply this skill when the user asks about labels or wants to see the available labels (e.g. "what labels do I have?", "show me all labels", "which labels can I use for places?").

## Label Guidelines & Actions
1. **Listing Labels**
   - When the user asks to browse or list available labels, call `get_labels`.
   - Present each label by its name. **NEVER invent labels that are not returned by the tool.**
2. **Extracting Label ID**
   - Once the target label is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**
