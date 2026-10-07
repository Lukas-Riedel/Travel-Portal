# Stay Skill

## Purpose
Provides capabilities to query and inspect accommodation stays across trips in Travel Portal.

## Stay Entity Structure
A stay object contains:
- `name` (string): Name of the accommodation (e.g. `"Jumeirah Burj Al Arab"`, `"MSC Grandiosa"`).
- `address` (string | null): Physical address of the property. Null when the stay is a cruise.
- `trip_id` (string): Unique identifier of the parent trip. Use it to correlate stays with other trip data.
- `trip_name` (string): Name of the parent trip.
- `start` (string): Check-in date in the user's local timezone (e.g. `"2026-10-08"`).
- `end` (string): Check-out date in the user's local timezone (e.g. `"2026-10-12"`).
- `nights` (integer): Number of nights spent at the accommodation.

## Stay Guidelines & Actions
1. **Listing Stays for a Specific Trip**:
   - When the user asks about accommodation on a particular trip (e.g. "where did I stay in Japan?", "what hotels did I book for my Egypt trip?"), call `get_stays_for_trip(trip_id)`.
   - Always resolve the trip ID first using trip tools (`get_current_trip`, `get_upcoming_trip`, or `get_trip_by_name`). **NEVER ask the user for a trip ID or UUID.**
   - **Trip objects returned by trip tools do NOT contain stay data.** You **MUST ALWAYS** call `get_stays_for_trip(trip_id)` explicitly — never conclude that no accommodation exists based on the trip object alone.
   - Present the stay name, check-in and check-out dates, number of nights, and address. If `address` is null, refer to the stay as a **cruise**, not a hotel.
2. **Listing All Stays across Trips**:
   - When the user asks about accommodation across multiple trips (e.g. "where have I stayed this year?", "list all my hotels"), call `get_all_stays(year)`.
   - Provide the `year` argument whenever the user mentions a specific year, to narrow the results.
   - Present stays grouped by trip (use `trip_name`) or chronologically — choose whichever is clearer given the user's question.
3. **Cruise Detection**:
   - When `address` is null, the stay is a cruise. Refer to it as a **cruise** and do not mention an address. Never call a cruise a hotel or accommodation property.
