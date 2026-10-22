# Place Label Read Skill

## Purpose & Activation
Provides capabilities to query and inspect labels assigned to places in Travel Portal.
Activate and apply this skill when the user asks about labels assigned to a specific place (e.g. "what labels does Prague have?", "show me the labels for Angkor Wat").

## Place Label Guidelines & Actions
1. **Listing Labels for a Place**
   - When the user asks which labels are assigned to a place, resolve the place first using place tools, then call `get_labels_for_place`. **NEVER ask the user for a place ID or UUID.**
   - **Place objects returned by place tools do NOT contain label data.** You **MUST ALWAYS** call `get_labels_for_place` explicitly - never conclude that a place has no labels based on the place object alone.
   - Present label names clearly and in the user's language.
2. **Extracting Label ID**
   - Once the target label is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**
