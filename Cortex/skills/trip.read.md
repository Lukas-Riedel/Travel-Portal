# Trip Read Skill

## Purpose & Activation
Provides capabilities to query, search, and locate trips in Travel Portal.
Activate and apply this skill when the user asks about trips, itineraries, travel history, upcoming trips, or searches for trips by name/destination.

## Trip Guidelines & Actions
1. **Obtaining Upcoming Trip**
   - When the user refers to the next or upcoming trip, call `get_upcoming_trip`.
2. **Obtaining Current Trip**
   - When the user refers to the current or ongoing trip, call `get_current_trip`.
   - If null, consider falling back to `get_upcoming_trip` or ask for clarification.
3. **Obtaining Trip by Name or Destination**
   - When the user mentions a specific trip or country (e.g. "a trip to Vietnam"), call `get_trip_by_name` with the destination as query.
   - If no match is found, ask the user for clarification, or use tools for listing. This can happen fairly often since the tool performs a full-text search.
4. **Listing Regular Trips**
   - When the user asks about available trips, call `get_regular_trips`. The listing contains all planned trips, even those planned for the near future.
   - Apply filters based on what the user mentions:
      - Provide `year` whenever the user mentions a specific year.
   - Calling the tool without filters can potentially take a very long time. Try to narrow down the expected result set using available filters, if possible.
   - Present trip name, formatted local start and end dates (e.g. "12. 5. 2025 – 20. 5. 2025"), visited countries (if not obvious from the name - e.g. for the trip named "Sardinia", it is obvious that the only visited country is Italy, but for the trip named "East Asia" it is completely unclear which are the visited countries from the name alone), and days.
5. **Listing Candidate Trips**
   - When the user asks about unplanned or candidate trips, call `get_candidate_trips`.
   - Candidate trips do not necessarily represent a wishlist. A candidate trip is typically a trip that was already planned but was called off for whatever reason.
6. **Public Holidays**
   - When the user asks which public holidays occur during a specific trip (e.g. "what public holidays are during my Japan trip?", "show public holidays for my 2026 trips"), resolve the trip first and call `get_trip_public_holidays` with its ID. **NEVER ask the user for a trip ID or UUID.**
   - Present the returned holidays in a structured list or table, including their name and date. Do not infer holidays that are not returned by the tool.
7. **Extracting Trip ID**
   - Once the target trip is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**
