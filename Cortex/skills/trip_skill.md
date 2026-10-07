# Trip Skill

## Purpose
Provides capabilities to query, search, and locate trips in Travel Portal.

## Trip Entity Structure
A trip object contains:
- `id` (string): Unique identifier of the trip. Use it when looking up stays, flights, public holidays and others.
- `name` (string): Title/name of the trip (e.g., "Southwestern United States").
- `year` (integer): Year of the trip. Null for candidate trips.
- `start` (string): Start date in ISO 8601 UTC format (e.g. `"2025-12-31T23:59:59Z"`). Null for candidate trips.
- `end` (string): End date in ISO 8601 UTC format (e.g. `"2025-12-31T23:59:59Z"`). Null for candidate trips.
- `days` (integer): Number of calendar days the trip spans (inclusive). Null for candidate trips.
- `countries` (list of strings | null): Countries visited on the trip, excluding layovers. Empty if the trip doesn't have any itinerary yet.
- `notes` (list of strings | null): Notes relevant for the trip.

## Trip Guidelines & Actions
1. **Obtaining Upcoming Trip**: When the user refers to the next or upcoming trip, call `get_upcoming_trip()`.
2. **Obtaining Current Trip**: When the user refers to the current or ongoing trip, call `get_current_trip()`. If null, consider falling back to `get_upcoming_trip()` or ask for clarification.
3. **Obtaining Trip by Name or Destination**: When the user mentions a specific trip or country (e.g. "a trip to Vietnam"), call `get_trip_by_name("Vietnam")`. If null, ask the user for clarification or check upcoming trips.
4. **Listing Regular Trips**:
   - When the user asks about available trips, call `get_regular_trips(year, sort)`:
      - `year`: The year of the trips to return. This parameter is optional. Provide it whenever appropriate to narrow the results.
      - `sort`: Supported sorting strategies: `oldest`, `-oldest`, `longest`, `-longest`.
   - Present trip name, start and end dates, visited countries (if not clear from the name), and days to the user. Do not present notes in a structured way - they exist mainly to extend your internal context.
5. **Listing Candidate Trips**:
   - When the user asks about available trips, call `get_candidate_trips()`.
   - Present trip name, start and end dates, visited countries (if not clear from the name), and days to the user. Do not present notes in a structured way - they exist mainly to extend your internal context.
8. **Extracting Trip ID**: Once the target trip is found, use its `id` for subsequent actions (like creating notes, expenses, or tasks). **NEVER invent a fake UUID**.