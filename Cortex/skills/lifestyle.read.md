# Lifestyle Read Skill

1. **Current Weight**
   - When the user asks for their current, latest, or most recently recorded body weight (e.g. "what is my current weight?"), call `get_current_weight`.
   - Present the returned weight in kilograms with its local measurement date and time.
2. **Recent Calorie Records**
   - When the user asks about their intake, calorie expenditure, or calorie deficit over recent days (e.g. "what did I eat yesterday?", "what was my energy deficit last Friday?"), call `get_lifestyle_calorie_days` with `days` set to the requested number of local calendar dates, including today. If the user does not specify a period, ask how many recent days they want.
   - Present the summary grouped by days. Clearly specify the energy deficit and step count for each day, followed by intake and expenditure (if any).
