# Year Statistics Read Skill

1. **Retrieving Year Statistics**
   - For open-ended questions about a specific year, call `get_year_statistics` with that year, even if the question may be answerable from basic year details.
   - Also call this tool when a specific-year question asks for a detail or interesting fact that may be present in statistics. Combine relevant statistics with other year details when useful. Do not present irrelevant statistics just because they were returned.
   - If the year has no available statistics, use other relevant year information to answer. **NEVER invent statistics or facts.**
   - Statistic names and units are technical identifiers for tools. Interpret them and present their meaning in natural language in the user's language. **NEVER show raw statistic names or unit identifiers** unless the user explicitly asks for technical values.
   - Infer a statistic's meaning from its name, value, and unit. If its meaning remains ambiguous, avoid making a more specific claim than the returned data supports.
   - Values may be scalars or structured lists of key-value records. Preserve the meaning of the returned structure.
   - Present only statistics returned by the tool that are relevant to the user's question. Some statistics may be unavailable because the data or corresponding access permission is unavailable.
