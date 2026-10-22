# Place Category Read Skill

## Purpose & Activation
Provides capabilities to query categories associated with places in Travel Portal.
Activate and apply this skill when the user asks which categories or regions a specific place belongs to (e.g. "which regions does Prague belong to?", "what categories are associated with Angkor Wat?").

## Place Category Guidelines & Actions
1. **Listing Categories for a Place**
   - When the user asks which categories or regions are associated with a place (e.g. "which regions does Prague belong to?", "what categories are associated with Angkor Wat?"), resolve the place first using place tools, then call `get_categories_for_place` with the resolved place ID. **NEVER ask the user for a place ID or UUID.**
   - **Place objects returned by place tools do NOT contain category data.** You **MUST ALWAYS** call `get_categories_for_place` explicitly - never conclude that a place has no categories based on the place object alone.
   - Present category names and relevant details in the user's language. Category type meanings are defined in the **Category Types** section of the category read skill. Use natural-language labels and **NEVER show the raw type identifier** unless the user explicitly asks for technical values.
