# Trip Skill

## Purpose
Provides capabilities to query, search, and locate trips in Travel Portal, as well as managing trip-associated entities such as notes.

## Trip Entity Structure
A trip object contains:
- `id` (string, UUID): Unique identifier of the trip.
- `name` (string): Title/name of the trip (e.g., "Las Vegas & Grand Canyon 2025").
- `year` (integer): Year of the trip.
- `start` (integer, Unix epoch seconds): Raw start timestamp — **prefer `start_iso` for display and reasoning**.
- `end` (integer, Unix epoch seconds): Raw end timestamp — **prefer `end_iso` for display and reasoning**.
- `start_iso` (string, ISO 8601 UTC): Human-readable start date, e.g. `"2025-07-05T08:00:00Z"`. Use this when talking about or comparing dates.
- `end_iso` (string, ISO 8601 UTC): Human-readable end date, e.g. `"2025-07-19T20:00:00Z"`. Use this when talking about or comparing dates.
- `duration_days` (integer): Number of calendar days the trip spans (inclusive). Use this when the user asks how long a trip is.
- `countries` (list of strings): Countries visited.

## Date & Duration Guidelines
- Always use `start_iso` / `end_iso` when displaying or reasoning about trip dates — never present raw epoch integers to the user.
- When the user asks "when does the trip start/end?", answer with the date portion of `start_iso` / `end_iso` (e.g. "5 July 2025").
- When the user asks "how long is the trip?", use `duration_days` directly (e.g. "14 days").
- `start_iso` and `end_iso` are UTC. If the user asks for local time and the timezone is known, convert accordingly; otherwise state that times are in UTC.

## Trip Selection Guidelines & Tools
1. **Upcoming Trip**: When the user refers to the next / upcoming trip, call `get_upcoming_trip()`.
2. **Current / Active Trip**: When the user refers to the current or ongoing trip, call `get_current_trip()`. If null, consider falling back to `get_upcoming_trip()` or ask for clarification.
3. **Trip by Name or Destination**: When the user mentions a specific trip or country (e.g. "a trip to Vietnam"), call `find_trip_by_name(query="Vietnam")`. If empty, consider falling back to `get_upcoming_trip()` or ask for clarification.
4. **All Trips**: To inspect or list trips manually, call `get_trips(year=None, trip_type="regular")`.
5. **Extracting Trip ID**: Once the target trip is found, use its `id` for subsequent actions (like creating notes or expenses).

## Note Creation Guidelines
1. **Target Trip**: A valid `trip_id` is required. If not known, retrieve trips first using `get_trips`, `get_upcoming_trip`, `get_current_trip`, or `find_trip_by_name`. Never create notes for past trips unless explicitly confirmed by the user.
2. **Content Formatting**:
   - The note `content` must be well-structured in **Markdown**.
   - Use headings (`#`, `##`), bullet points, bold text, and numbered lists where appropriate. Short notes don't have to have formatting.
   - For travel recommendations or itineraries, structure by categories or days (e.g., Highlights, Food & Dining, Tips).
   - Match the language requested or used by the user in the prompt.
3. **Execution**:
   - Call `create_trip_note(trip_id=trip_id, content=content)`.
4. **Result Reporting**:
   - Confirm to the user that the note was created for the specified trip with a short summary.
