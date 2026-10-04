# Trip Skill

## Purpose
Provides capabilities to query, search, and locate trips in Travel Portal.

## Trip Entity Structure
A trip object contains:
- `id` (string, UUID): Unique identifier of the trip.
- `name` (string): Title/name of the trip (e.g., "Las Vegas & Grand Canyon 2025").
- `year` (integer): Year of the trip.
- `start` (integer, epoch seconds): Start date and time of the trip.
- `end` (integer, epoch seconds): End date and time of the trip.
- `countries` (list of strings): Countries visited.

## Trip Selection Guidelines & Tools
1. **Upcoming Trip**: When the user refers to the next / upcoming trip, call `get_upcoming_trip()`.
2. **Current / Active Trip**: When the user refers to the current or ongoing trip, call `get_current_trip()`. If null, consider falling back to `get_upcoming_trip()` or ask for clarification.
3. **Trip by Name or Destination**: When the user mentions a specific trip or country (e.g. "a trip to Vietnam"), call `find_trip_by_name(query="Vietnam")`. If empty, consider falling back to `get_upcoming_trip()` or ask for clarification.
4. **All Trips**: To inspect or list trips manually, call `get_trips(year=None, trip_type="regular")`.
5. **Extracting Trip ID**: Once the target trip is found, use its `id` for subsequent actions (like creating notes or expenses).
