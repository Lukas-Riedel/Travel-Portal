# Flight Edit Skill

1. **Preparing Flight Details**
   - When the user pastes a booking confirmation or other flight information and asks to add the flight, extract the flight number, origin airport, destination airport, departure date and local time, and arrival date and local time from the provided text. Use the confirmation as the source of truth. **NEVER invent missing flight details**.
   - If the user explicitly asks to watch, monitor, or track a flight, prepare it as a watched flight. Otherwise, a request to add or record a booked flight means a scheduled flight.
   - A flight segment requires all of these values: `flight`, `origin_airport_name`, `destination_airport_name`, `scheduled_departure`, and `scheduled_arrival`. If any value is missing, unreadable, or ambiguous, **ASK THE USER** only for the missing or unclear information before calling a creation tool. **NEVER create a flight with incomplete details.**
   - When all required details are present and unambiguous in the user's message or pasted confirmation, proceed directly without asking the user to repeat or confirm them.
   - If the confirmation contains multiple distinct flight segments, treat each segment separately and create one flight per segment. Ask the user to clarify any segment whose details are incomplete or unclear.
   - If the confirmation provides only a date and no departure or arrival time, ask the user for the missing departure and/or arrival time. **NEVER invent a time** from the flight number, route, or another segment.
2. **Creating Scheduled Flights**
   - When the user asks to add a scheduled or booked flight (e.g. "add flight EK139 from Prague to Dubai on July 5th", "create a scheduled flight LH1234"), call `create_scheduled_flight`.
   - For timestamps (`scheduled_departure` and `scheduled_arrival`):
      - Ensure you obtain or compute Unix epoch timestamps in seconds. Use time tools if conversion from a date or time string is needed.
      - Unless stated otherwise, provided date or time is in local time. The `scheduled_departure` is in a local timezone of the origin airport. The `scheduled_arrival` is in a local timezone of the destination airport. You **MUST ALWAYS** resolve each timezone separately by using geocoding tools and use them to compute Unix epoch timestamps. Use the corresponding `origin_airport_name` or `destination_airport_name` as the address.
      - For date/time values from an email, preserve the date and time shown for each airport. Account for any stated timezone or UTC offset when converting. Ask the user if the timezone or date/time is unclear. **NEVER assume the departure and arrival use the same timezone or calendar date**.
   - For airport names (`origin_airport_name` and `destination_airport_name`):
      - Pass the airport city or name as mentioned by the user (e.g. "Prague", "Dubai").
   - After creating a flight, confirm with a concise summary stating the flight number, departure and arrival airports, and departure and arrival dates and times.
3. **Creating Watched Flights**
   - When the user asks to watch, monitor, or track a flight (e.g. "track flight BA854 from London to Prague", "watch flight LH1234 from Munich to Frankfurt"), call `create_watched_flight`.
   - For timestamps (`scheduled_departure` and `scheduled_arrival`):
      - Ensure you obtain or compute Unix epoch timestamps in seconds. Use time tools if conversion from a date or time string is needed.
      - Unless stated otherwise, provided date or time is in local time. The `scheduled_departure` is in a local timezone of the origin airport. The `scheduled_arrival` is in a local timezone of the destination airport. You **MUST ALWAYS** resolve each timezone separately by using geocoding tools and use them to compute Unix epoch timestamps. Use the corresponding `origin_airport_name` or `destination_airport_name` as the address.
      - For date/time values from an email, preserve the date and time shown for each airport. Account for any stated timezone or UTC offset when converting. Ask the user if the timezone or date/time is unclear. **NEVER assume the departure and arrival use the same timezone or calendar date**.
   - For airport names (`origin_airport_name` and `destination_airport_name`):
      - Pass the airport city or name as mentioned by the user (e.g. "London", "Prague").
   - After creating a watched flight, confirm with a concise summary stating the flight number, departure and arrival airports, dates and times, and that the flight is now being watched.
