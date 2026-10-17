# Place Read Skill

## Purpose & Activation
Provides capabilities to query, search, and inspect places in Travel Portal.
Activate and apply this skill when the user asks about visited places, destinations, locations, points of interest, candidate places, or wants to know when a specific place was visited.

## Place Guidelines & Actions
1. **Listing Places**:
   - When the user asks to browse or list places, call `get_places`.
   - Apply filters based on what the user mentions:
     - Trip → resolve the trip ID first using trip tools, then pass `trip_id`. **Never ask the user for a UUID.**
     - Label → resolve the label ID first using label tools, then pass `label_id`. **Never ask the user for a UUID.**
     - Category (Country) → resolve the category ID first using category tools, then pass `category_id`. **Never ask the user for a UUID.**
     - Year → pass `year`.
     - Date interval → pass `min_start` and/or `max_end`. Use `convert_datetime_to_epoch` with the place's timezone to convert user-provided dates. The listing may contain future places if there is no upper bound.
   - **Calling the tool without filters can potentially take a very long time. ALWAYS try to narrow down the expected result set if possible.**
   - Pass `sort` when the user asks for ordering.
   - Present each place by its name, the rest depends on the context (e.g. the listing can contain the place country if ambiguous, as well as visited dates and/or times).
   - When asking about the trip itinerary, group the places by their dates, present also time slots.
   - Do not present notes unless the user explicitly asks for them - they extend your internal context only.
2. **Listing Candidate Places**: When the user asks about unplanned, wishlist, or candidate places, call `get_candidate_places`. Present results the same way as regular places.
3. **Listing Dates for a Place**:
   - When the user asks when a specific place was visited (e.g. "when did I visit Prague?", "how many times have I been to Kyoto?"), call `get_place_dates`.
   - Dates include a `weather_forecast` field with forecast data - present it when the date is in the future or when the user asks about weather. Forecast data are not available if the date is too distant in the future.
   - Present each date as a formatted interval (e.g. `12. 5. 2025 – 15. 5. 2025`) plus trip name if available.
   - A date represents a timespan actively spent by sightseeing. If there are multiple dates for the same place close to each other, refer to it as a single visit of that place.
4. **Finding a Place by Name**:
   - When you need a place ID from a user-provided name, call `get_place_by_name`.
   - If no match is found, ask the user for clarification.
   - **NEVER invent a fake UUID.**
5. **Getting an Excerpt for a Place**: When the user asks for a description, summary, or excerpt of a specific place, call `get_place_excerpt`.
   - Present the excerpt as a standalone descriptive paragraph.
