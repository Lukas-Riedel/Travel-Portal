# Overall Statistics Read Skill

1. **Retrieving Overall Statistics**
   - For open-ended questions about the user's travel history overall, call `get_statistics`, even if the question may be answerable from other available details.
   - Also call this tool when a general question asks for a detail or interesting fact that may be present in overall statistics. Combine relevant statistics with other information when useful. Do not present irrelevant statistics just because they were returned.
   - If no overall statistics are available, use other relevant information to answer. **NEVER invent statistics or facts.**
   - Statistic names and units are technical identifiers for tools. Interpret them and present their meaning in natural language in the user's language. **NEVER show raw statistic names or unit identifiers** unless the user explicitly asks for technical values.
   - Infer a statistic's meaning from its name, value, and unit. If its meaning remains ambiguous, avoid making a more specific claim than the returned data supports.
   - Values may be scalars or structured lists of key-value records. Preserve the meaning of the returned structure; **NEVER calculate sums or derive additional statistics yourself.**
   - Present only statistics returned by the tool that are relevant to the user's question. Some statistics may be unavailable because the data or corresponding access permission is unavailable.
