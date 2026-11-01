# Trip Stay Read Skill

1. **Listing Stays**
   - When the user asks about accommodation (e.g. "where did I stay in Japan?", "what hotels did I book for my Egypt trip?", "where have I stayed this year?", "list all my hotels"), call `get_stays`.
   - Apply filters based on what the user mentions:
      - Provide `trip_id` whenever the user mentions a specific trip - resolve it first using trip tools. **NEVER ask the user for a trip ID or UUID.**
      - Provide `year` whenever the user mentions a specific year. Ignored when `trip_id` is set.
   - **Trip objects returned by trip tools do NOT contain stay data.** You **MUST ALWAYS** call `get_stays` explicitly - never conclude that no accommodation exists based on the trip object alone.
   - Present the stay name, check-in and check-out dates, number of nights, and address. A short summary of the hotel can be included as well, if available (e.g. its location or features). If `address` is null, refer to the stay as a **cruise**, not a hotel.
2. **Listing Cruises**
   - When the user asks specifically about cruises or ships (e.g. "which cruises have I been on?", "list all my cruises", "what ships did I sail on this year?", "did I go on a cruise during my Mediterranean trip?"), call `get_cruises`.
   - Apply filters based on what the user mentions:
      - Provide `trip_id` whenever the user mentions a specific trip - resolve it first using trip tools. **NEVER ask the user for a trip ID or UUID.**
      - Provide `year` whenever the user mentions a specific year. Ignored when `trip_id` is set.
   - Use `get_stays` instead when the user asks about accommodation in general, including hotels.
   - Present cruises grouped by trip or chronologically - choose whichever is clearer given the user's question.
3. **Cruise Detection**
   - When `address` is null, the stay is a cruise. Refer to it as a **cruise** and do not mention an address. Never call a cruise a hotel or accommodation property.
   - The name of the cruise represents the name of the ship. Find relevant information about the ship and provide it in a concise way.
