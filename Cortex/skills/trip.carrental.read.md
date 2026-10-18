# Trip Car Rental Read Skill

## Purpose & Activation
Provides capabilities to query and inspect car rentals across trips in Travel Portal.
Activate and apply this skill when the user asks about rented cars or car rentals for a specific trip or across all trips.

## Car Rental Guidelines & Actions
1. **Listing Car Rentals for a Specific Trip**
   - When the user asks about a rented car on a particular trip (e.g. "what car did I rent in Iceland?", "did I have a rental car in Sardinia?"), call `get_car_rentals_for_trip`.
   - Always resolve the trip ID first using trip tools (`get_current_trip`, `get_upcoming_trip`, or `get_trip_by_name`). **NEVER ask the user for a trip ID or UUID.**
   - **Trip objects returned by trip tools do NOT contain car rental data.** You **MUST ALWAYS** call `get_car_rentals_for_trip` explicitly - never conclude that no car was rented based on the trip object alone.
   - Present the car model, the first and the return day, and the number of days.
2. **Listing All Car Rentals across Trips**
   - When the user asks about rented cars across multiple trips (e.g. "which cars have I rented this year?", "list all my car rentals"), call `get_all_car_rentals`.
   - Provide the `year` argument whenever the user mentions a specific year.
   - Present car rentals grouped by trip or chronologically - choose whichever is clearer given the user's question.
