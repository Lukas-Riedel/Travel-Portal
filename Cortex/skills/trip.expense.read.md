# Trip Expense Read Skill

1. **Listing Expenses**
   - When the user asks about expenses, costs, or spending, call `get_expenses`.
   - Apply filters based on what the user mentions:
      - Provide `trip_id` whenever the user mentions a specific trip - resolve it first using trip tools. **NEVER ask the user for a trip ID or UUID.**
      - Provide `year` whenever the user mentions a specific year. Ignored when `trip_id` is set.
      - Provide `expense_type` whenever the user mentions a category of expenses. Map the user's wording to the closest type using the expense type definitions below.
   - **Trip objects returned by trip tools do NOT contain expense data.** You **MUST ALWAYS** call `get_expenses` explicitly - never conclude that no expense exists based on the trip object alone.
   - Present expenses grouped by trip, and within a trip by category. Display description and `price` (the amount actually paid, with its currency).
   - Name categories in natural language in the user's language (e.g. flights, accommodation, transport). **NEVER show the raw type identifier (such as `flight`, `hotel` or `organizedTour`) or any other field name to the user.**
   - Use `converted_price` (the amount in the user's main currency) when comparing expenses. **NEVER add up `price` values in different currencies.**
   - The `converted_price` is computed with the exchange rate from the time the expense was created (or its currency last changed), not the current rate. **NEVER present it as today's value** - when the user asks what an expense would cost now, explain that the stored conversion is historical.
   - Mention the `subscription` when it is set.
2. **Listing Expense Totals**
   - When the user asks for a total, sum, or "how much in total" (e.g. "how much did I spend on my Japan trip?", "total cost of flights this year"), call `get_expense_total` with the same filters as `get_expenses` (`trip_id`, `year`, `expense_type`).
   - Present the `total` with its currency. When listing the expenses together with the total, call both `get_expenses` and `get_expense_total` and do not add any extra totals of your own (e.g. per currency).
   - The `total` is the sum of historical conversions, see the note on `converted_price` above. State that the total is an approximation based on the exchange rates from the time the expenses were recorded.
3. **Expense Types**
   - `flight` - airline tickets and flight-related fees paid directly for flights (e.g. "Prague - Istanbul", "extra luggage").
   - `transport` - local and intercity transport other than flights and rental cars: trains, buses, metro, ferries, taxis, ride-hailing, airport transfers, transit passes and tickets.
   - `car` - everything related to driving a car: rental fees, fuel, tolls, parking, car insurance.
   - `hotel` - accommodation: hotels, hostels, apartments, cruise cabins, camping, accommodation taxes.
   - `attraction` - admissions and activities booked individually: museums, monuments, tickets to sights, national parks, shows, single-purpose excursions.
   - `organizedTour` - packaged or guided tours and multi-day trips bought as one product from a tour operator or travel agency.
   - `visa` - visas, entry and exit fees, border fees, electronic travel authorizations.
   - `other` - reserved for truly unique expenses that fit none of the types above.
4. **Extracting Expense ID**
   - Once the target expense is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**
