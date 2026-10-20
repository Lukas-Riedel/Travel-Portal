# Trip Flight Read Skill

## Purpose & Activation
Provides capabilities to query and inspect scheduled and watched flights across trips in Travel Portal.
Activate and apply this skill when the user asks about flights, bookings, flight schedules, airlines flown with, airports, or tracked/watched flights for a specific trip or across all trips.

## Flight Guidelines & Actions
1. **Listing Scheduled Flights**
   - When the user asks about flights, booked flights, flight schedules, or flights matching a specific airline, airport, or aircraft type (e.g. "what flights do I have booked?", "show flights for my Japan trip", "did I fly with Emirates in 2024?", "list flights departing from Prague", "what flights were on A380?"), call `get_scheduled_flights`.
   - Apply filters based on what the user mentions:
      - Provide `trip_id` whenever the user mentions a specific trip - resolve it first using trip tools. **NEVER ask the user for a trip ID or UUID.**
      - Provide `year` whenever the user mentions a specific year. Ignored when `trip_id` is set.
      - Provide `airline` when the user mentions an airline name or code (e.g. "Emirates", "EK", "Ryanair").
      - Provide `airport` when the user mentions an airport name, city, or IATA code (e.g. "PRG", "Prague", "DXB").
      - Provide `aircraft` when the user mentions an aircraft model or code (e.g. "A388", "B738", "A320").
   - Calling the tool without filters can potentially take a very long time. Try to narrow down the expected result set using available filters, if possible.
   - **Trip objects returned by trip tools do NOT contain flight data.** You **MUST ALWAYS** call `get_scheduled_flights` explicitly - never conclude that no flights exist based on the trip object alone.
   - Scheduled flights can be planned or logged. If both the aircraft type and registration are unavailable, treat the flight as planned/in the future. If either is present, treat it as a logged, departed flight. Do not infer flight status from the trip dates.
   - Present the flight number, airline name, origin and destination airports, departure and arrival dates and times, aircraft type, distance, and duration. Flight dates and times are already in the local timezone of their respective airports. Present them as local times and do not convert them unless the user asks.
   - Flight duration is returned in seconds. **MUST ALWAYS** call `convert_seconds_to_duration` to convert it to hours, minutes, and seconds before presenting it. **NEVER calculate or convert the duration yourself.**
2. **Listing Watched Flights**
   - When the user asks specifically about watched or monitored flights (e.g. "what flights am I watching?", "show watched flights for my next trip", "list all watched flights this year"), call `get_watched_flights`.
   - Apply filters based on what the user mentions:
      - Provide `trip_id` whenever the user mentions a specific trip - resolve it first using trip tools. **NEVER ask the user for a trip ID or UUID.**
      - Provide `year` whenever the user mentions a specific year. Ignored when `trip_id` is set.
   - **Trip objects returned by trip tools do NOT contain watched flight data.** You **MUST ALWAYS** call `get_watched_flights` explicitly - never conclude that no watched flights exist based on the trip object alone.
   - Present the flight number, origin and destination airports, scheduled departure and arrival dates and times, and indicate that the flight is being watched. Flight dates and times are already in the local timezone of their respective airports; present them as local times and do not convert them unless the user asks.
   - Flight duration is returned in seconds. **MUST ALWAYS** call `convert_seconds_to_duration` to convert it to hours, minutes, and seconds before presenting it. **NEVER calculate or convert the duration yourself.**
