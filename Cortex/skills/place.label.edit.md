# Place Label Edit Skill

1. **Assigning Label to Place**
   - Resolve the place first using place tools. **NEVER ask the user for a place ID or UUID.**
   - **MUST ALWAYS** inspect the full list of available labels before choosing one. Select a label only when one available label clearly matches the user's request.
   - If more than one existing label could match, **ASK THE USER** which one to use.
   - If no available label clearly matches, propose a suitable label name and **ASK THE USER** whether they want to create it. **EXPLICITLY WAIT FOR THE USER'S CONFIRMATION** before calling the assignment tool. **NEVER create a new label without the user's explicit confirmation.**
   - Once the user confirms, call `assign_label_to_place` with the resolved place and the confirmed label name. The tool will create the label and assign it to the place.
   - After assigning the label, confirm with a concise summary naming the place and label.
