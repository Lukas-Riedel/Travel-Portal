# Trip Car Rental Read Skill

## Purpose & Activation
Provides capabilities to query and inspect car rentals across trips in Travel Portal.
Activate and apply this skill when the user asks about rented cars or car rentals for a specific trip or across all trips.

## Car Rental Guidelines & Actions
1. **Listing Car Rentals**
   - When the user asks about rented cars (e.g. "what car did I rent in Iceland?", "did I have a rental car in Sardinia?", "which cars have I rented this year?", "list all my car rentals"), call `get_car_rentals`.
   - Apply filters based on what the user mentions:
      - Provide `trip_id` whenever the user mentions a specific trip - resolve it first using trip tools. **NEVER ask the user for a trip ID or UUID.**
      - Provide `year` whenever the user mentions a specific year. Ignored when `trip_id` is set.
   - **Trip objects returned by trip tools do NOT contain car rental data.** You **MUST ALWAYS** call `get_car_rentals` explicitly - never conclude that no car was rented based on the trip object alone.
   - Present the car model, the first and the return day, and the number of days.
