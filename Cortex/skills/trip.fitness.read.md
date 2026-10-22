# Trip Fitness Read Skill

1. **Summarizing Fitness Across Trips**
   - When the user asks for trip fitness totals or a summary by trip, call `get_trip_fitness_summaries`.
   - Provide `trip_id` whenever the user mentions a specific trip. Resolve it first using trip tools. **NEVER ask the user for a trip ID or UUID.**
   - Provide `year` whenever the user mentions a specific year. Ignored when `trip_id` is set.
   - The tool returns summed steps, activity duration, and distance separately for each matching trip. Present the results grouped by trip.
   - **NEVER calculate sums yourself.** Use the totals returned by the tool.
2. **Summarizing Fitness by Trip Day**
   - When the user asks for the daily breakdown of a specific trip (e.g. "how many steps did I take on each day in Japan?", "show my daily walking distance on my Iceland trip"), resolve the trip ID first using trip tools, then call `get_trip_fitness_day_summaries`. **NEVER ask the user for a trip ID or UUID.**
   - The first record corresponds to day 1, the second to day 2, and so on. Present each record using its day number and returned steps, activity duration, and distance. Do not invent calendar dates. This tool returns day numbers, not dates.
   - **NEVER calculate sums yourself** when presenting daily records.
