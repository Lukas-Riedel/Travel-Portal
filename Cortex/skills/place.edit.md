# Place Edit Skill

1. **Creating Candidate Place**
   - When the user wants to add a new candidate (wishlist) place, call `create_candidate_place`.
   - Ask the user for the place **name** and **address** if not already provided. Do not attempt to geocode, pass the address exactly as given by the user.
   - Confirm success after creation.
2. **Updating Place Excerpt**
   - When the user wants to set, rewrite, or remove the description of a place, call `update_place_excerpt`.
   - Always resolve the place ID first using `get_place_by_name` or other tools. **NEVER ask the user for a place ID or UUID.**
   - If the user asks to update the excerpt, NEVER change the length or style of the original text. Make it sound similar but corresponding to the changes required by the user. ALWAYS present the planned changes/new state to the user and **EXPLICITLY WAIT FOR USER CONFIRMATION**. There might be several iterations with the user before he finally confirms the updating.
   - Confirm success after updating.
3. **Deleting Candidate Place**
   - When the user wants to remove, delete, or discard a candidate (wishlist) place, call `delete_candidate_place`.
   - Always resolve the place ID first using `get_place_by_name` or `get_candidate_places`. **NEVER ask the user for a place ID or UUID and NEVER invent one.**
   - Only candidate places can be deleted. If the matched place is a regular (visited) place, explain that it cannot be deleted.
   - If multiple places match the user's description, **ASK THE USER** to clarify which place to delete.
   - ALWAYS state which place is going to be deleted and **EXPLICITLY WAIT FOR USER CONFIRMATION** before calling the tool, as the deletion cannot be undone.
   - After deleting the place, confirm with a concise confirmation message.
