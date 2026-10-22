# Category Edit Skill

## Purpose & Activation
Provides capabilities to update category colors and public-holiday calendar URLs in Travel Portal.
Activate and apply this skill when the user asks to change a category's color or public holidays calendar (e.g. "set the color of Europe to #012169", "set the Czech Republic public holidays calendar to URL").

## Category Guidelines & Actions
1. **Updating a Category Color**
   - When the user asks to set or change a category's color (e.g. "set the color of Europe to #012169"), call `update_category_color`.
   - Always resolve the category ID first using `get_category_by_name` or other tools. **NEVER ask the user for a category ID or UUID.**
   - If the user has not provided an exact color value, ask what color value to use. **NEVER invent a color.**
   - After a successful update, confirm the category name and the new color concisely.
2. **Updating a Public Holidays Calendar**
   - When the user asks to set or change a category's public holidays calendar (e.g. "set the Czech Republic public holidays calendar to URL"), call `update_category_public_holidays_calendar`.
   - Always resolve the category ID first using `get_category_by_name` or other tools. **NEVER ask the user for a category ID or UUID.**
   - If the user has not provided a calendar URL or it is ambiguous, ask for the URL. **NEVER invent a calendar URL.**
   - After a successful update, confirm the category name and that its public holidays calendar was updated.
