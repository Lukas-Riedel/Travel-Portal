# Trip Skill

## Purpose
Provides capabilities to query, search, and locate trips in Travel Portal.

## Trip Guidelines & Actions
1. **Obtaining Upcoming Trip**: When the user refers to the next or upcoming trip, call `get_upcoming_trip`.
2. **Obtaining Current Trip**: When the user refers to the current or ongoing trip, call `get_current_trip`. If null, consider falling back to `get_upcoming_trip` or ask for clarification.
3. **Obtaining Trip by Name or Destination**: When the user mentions a specific trip or country (e.g. "a trip to Vietnam"), call `get_trip_by_name` with the destination as query. If null, ask the user for clarification or check upcoming trips.
4. **Listing Regular Trips**:
   - When the user asks about available trips, call `get_regular_trips`.
   - Provide `year` whenever the user mentions a specific year to narrow the results.
   - Present trip name, formatted local start and end dates (e.g. "12. 5. 2025 – 20. 5. 2025"), visited countries (if not clear from the name), and days. Do not present notes in a structured way — they exist mainly to extend your internal context.
5. **Listing Candidate Trips**: When the user asks about unplanned or candidate trips, call `get_candidate_trips`.
6. **Extracting Trip ID**: Once the target trip is found, use its `id` for subsequent actions (like creating notes, expenses, or tasks). **NEVER invent a fake UUID.**
