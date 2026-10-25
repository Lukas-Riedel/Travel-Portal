# Tracker Read Skill

1. **Getting Time Off Balances**
   - When the user asks how much vacation, sick days, overtime, or bonus time-off they have available (e.g. "how many vacation days do I have left?", "what is my overtime balance?"), call `get_time_off_balances`. Map the user's wording to the closest type using the time off type definitions below.
   - The tool returns all balances at once, in whole days. Present only the balance the user asked for, unless they ask for an overview.
   - Name the types in natural language in the user's language (e.g. vacation, sick days). **NEVER show the raw type identifier (such as `selfcare`) or any other field name to the user.**
   - The balances are only available in days. **NEVER convert them to hours or any other unit**. The days are already recalculated to the user's FTE (work load), which you do not know, so the length of one day in hours is unknown to you and any conversion would be wrong.
   - The balance already includes the time off that is planned in the future.
2. **Getting Planned Time Off**
   - When the user asks when their vacation, sick days, overtime, or bonus time-off is planned, or whether they have any time off planned (e.g. "when do I have my vacation planned?", "do I have any time off next month?"), call `get_planned_time_off`.
   - The tool returns the time off from today onwards, earliest first. Each item contains the type, the date, and the length in working days.
   - Present the dates clearly to the user. When consecutive dates have the same type, group them into a range (e.g. "22. 7. 2025 - 26. 7. 2025, 5 days of vacation").
   - The lengths are only available in days. **NEVER convert them to hours or any other unit**. The days are already recalculated to the user's FTE (work load), which you do not know, so any conversion would be wrong.
3. **Time Off Types**
   - `overtime` - overtime worked, which can be taken as time off.
   - `vacation` - regular vacation.
   - `selfcare` - classic sick days.
   - `bonus` - bonus time off granted by the management.
