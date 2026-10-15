# Place Edit Skill

## Purpose & Activation
Provides capabilities to create and update places in Travel Portal.
Activate and apply this skill when the user wants to add a new candidate place or update the description of an existing place.

## Place Guidelines & Actions
1. **Creating a Candidate Place**: When the user wants to add a new candidate (wishlist) place, call `create_candidate_place`.
   - Ask the user for the place **name** and **address** if not already provided. Do not attempt to geocode — pass the address exactly as given by the user.
   - Confirm success after creation.
2. **Updating a Place Excerpt**: When the user wants to set, rewrite, or remove the description of a place, call `update_place_excerpt`.
   - Always resolve the place ID first using `get_place_by_name`. **NEVER ask the user for a UUID.**
   - Confirm success after updating.
