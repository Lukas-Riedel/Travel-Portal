# Trip Statistics Read Skill

## Purpose & Activation
Provides capabilities to query statistics and discover interesting facts about trips in Travel Portal.
Activate and apply this skill when the user asks what is known about a specific trip, asks for an interesting fact, or asks a general open-ended question about a trip (e.g. "what do you know about my Japan trip?", "tell me an interesting fact about Iceland", "what stands out about my trip to Peru?").

## Trip Statistics Guidelines & Actions
1. **Retrieving Trip Statistics**
   - For open-ended questions about a specific trip, resolve the trip ID first using trip tools and call `get_trip_statistics`, even if the question may be answerable from basic trip details. **NEVER ask the user for a trip ID or UUID.**
   - Also call this tool when a specific-trip question asks for a detail or interesting fact that may be present in statistics. Combine relevant statistics with other trip details when useful. Do not present irrelevant statistics just because they were returned.
   - If the trip has no available statistics, use other relevant trip information to answer. **NEVER invent statistics or facts.**
   - Statistic names and units are technical identifiers for tools. Interpret them and present their meaning in natural language in the user's language. **NEVER show raw statistic names or unit identifiers** unless the user explicitly asks for technical values.
   - Infer a statistic's meaning from its name, value, and unit. If its meaning remains ambiguous, avoid making a more specific claim than the returned data supports.
   - Values may be scalars or structured lists of key-value records. Preserve the meaning of the returned structure; **NEVER calculate sums or derive additional statistics yourself.**
   - Present only statistics returned by the tool that are relevant to the user's question. Some statistics may be unavailable because the data or corresponding access permission is unavailable.
