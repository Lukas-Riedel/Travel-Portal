# Stay Skill

## Purpose
Provides capabilities to query and inspect accommodation stays across trips in Travel Portal.

## Stay Guidelines & Actions
1. **Listing Stays for a Specific Trip**:
   - When the user asks about accommodation on a particular trip (e.g. "where did I stay in Japan?", "what hotels did I book for my Egypt trip?"), call `get_stays_for_trip`.
   - Always resolve the trip ID first using trip tools (`get_current_trip`, `get_upcoming_trip`, or `get_trip_by_name`). **NEVER ask the user for a trip ID or UUID.**
   - **Trip objects returned by trip tools do NOT contain stay data.** You **MUST ALWAYS** call `get_stays_for_trip` explicitly — never conclude that no accommodation exists based on the trip object alone.
   - Present the stay name, check-in and check-out dates, number of nights, and address. If `address` is null, refer to the stay as a **cruise**, not a hotel.
2. **Listing All Stays across Trips**:
   - When the user asks about accommodation across multiple trips (e.g. "where have I stayed this year?", "list all my hotels"), call `get_all_stays`.
   - Provide the `year` argument whenever the user mentions a specific year, to narrow the results.
   - Present stays grouped by trip or chronologically — choose whichever is clearer given the user's question.
3. **Cruise Detection**:
   - When `address` is null, the stay is a cruise. Refer to it as a **cruise** and do not mention an address. Never call a cruise a hotel or accommodation property.
