# Place Read Skill

1. **Listing Places**
   - When the user asks to browse or list places, call `get_places`.
   - Apply filters based on what the user mentions:
      - Provide `year` whenever the user mentions a specific year.
      - Provide `trip_id` whenever the user mentions a specific trip (e.g. "show me the itinerary of the trip to Taiwan"), resolve the trip ID first using trip tools. **NEVER ask the user for a trip ID or UUID.**
      - Provide `label_id` whenever the user mentions a specific label or feature (e.g. "show me the list of caves that I visited"), resolve label ID first using label tools. **NEVER ask the user for a label ID or UUID.**
      - Provide `category_id` whenever the user mentions a specific region (e.g. "show me the list of places that I visited in the state of Oregon"), resolve category ID first using category tools. **NEVER ask the user for a category ID or UUID.**
      - Provide `min_start` and/or `max_end` whenever the user mentions a specific timespan (e.g. "show me the list of places that I visited between September and November"). Use `convert_datetime_to_epoch` to convert user-provided dates. **If the request does not define `max_end`, the listing may contain places that are planned to be visited in the near future. Consider this fact when crafting a final response.**
   - Calling the tool without filters can potentially take a very long time. Try to narrow down the expected result set using available filters, if possible.
   - Present each place by its name, the rest of displayed fields depends on the context (e.g. the listing can contain the place country if ambiguous, as well as visited dates and/or times).
   - When asking about the trip itinerary, group the places by their dates, present also time slots.
   - Dates are only returned if either `year`, `trip_id`, `label_id` or `category_id` filters are defined. If you need dates, you MUST define at least one of these filters, or list dates for each place individually (see below).
2. **Listing Candidate Places**
   - When the user asks about unplanned, wishlist, or candidate places, call `get_candidate_places`. Present results the same way as regular places.
3. **Listing Recently Visited Places**
   - When the user asks about their latest or last visited places (e.g. "where have I been recently?", "show me the last 5 places I visited"), call `get_recently_visited_places` instead of `get_places`.
   - Set `limit` to the number of places the user asks for. If the number is not specified, use a sensible default based on the context (e.g. 10).
   - Provide `category_id` whenever the user mentions a specific region (e.g. "the last places I visited in Hungary"), resolve the category ID first using category tools. **NEVER ask the user for a category ID or UUID.**
   - Places are returned latest first, including their visit dates. Use the sequence of the dates to reconstruct an itinerary and provide a broader context.
4. **Listing Nearby Places**
   - When the user asks what is near a specific place (e.g. "what else is around Prague?", "show me places close to Angkor Wat"), call `get_nearby_places`.
   - Set `limit` to the number of places the user asks for. If the number is not specified, use a sensible default based on the context (e.g. 5).
   - Provide `place_id` of the reference place, resolve the place ID first using `get_place_by_name`. **NEVER ask the user for a place ID or UUID.**
   - Nearby places can be both visited and candidate places. Use their visit dates, if present, to provide a broader context.
5. **Listing Dates for Place**
   - When the user asks when a specific place was visited (e.g. "when did I visit Prague?", "how many times have I been to Kyoto?"), call `get_place_dates`.
   - A date represents a timespan actively spent by sightseeing. If there are multiple dates for the same place close to each other, refer to it as a single visit of that place.
6. **Finding Place by Name**
   - When the user mentions a specific location, first evaluate whether it refers to a broad geographical entity, island, region, or administrative area (e.g., "Bali", "Sicily", "Oregon") rather than a specific settlement, monument, or attraction. If it does, resolve it using category tools. Otherwise, call `get_place_by_name` with the place as query.
   - If no match is found, ask the user for clarification, or use tools for listing. This can happen fairly often since the tool performs a full-text search.
7. **Getting Excerpt for Place**
   - When the user asks for a description, summary, or excerpt of a specific place, call `get_place_excerpt`.
   - Present the excerpt as a standalone descriptive paragraph.
8. **Extracting Place ID**
   - Once the target place is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**
