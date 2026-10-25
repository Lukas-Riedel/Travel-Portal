# Trip Expense Edit Skill

1. **Creating Trip Expense**
   - When the user asks to add, create, or record an expense for a trip, call `create_trip_expense`.
   - Always resolve the trip ID first using trip tools. **NEVER ask the user for a trip ID or UUID.**
   - The `value` and `currency` are required. **NEVER invent them** - ask the user when missing.
   - The `expense_type` must be one of the types defined in the **Expense Types** section of the trip expense read skill (`flight`, `transport`, `car`, `hotel`, `attraction`, `organizedTour`, `visa`, `other`). Choose the type whose definition best matches the user's request. Ask the user when the type is unclear between two types.
   - The type `other` is reserved for truly unique expenses that fit none of the other types. **NEVER assign `other` without the user's explicit confirmation.** When no other type fits, **ASK THE USER** to confirm `other` (mention the expense and the closest alternative type, if any) and only proceed with `get_expense_descriptions` and `create_trip_expense` after the user confirms. If the user suggests a different type, use it instead. This does not apply when the user explicitly asked for the type `other` in their request.
   - **MUST ALWAYS** call `get_expense_descriptions` with the chosen `expense_type` before creating the expense, and derive the `description` from the returned examples:
      - Follow the naming style, level of detail, and language of the examples.
      - Do not copy an example verbatim unless it denotes the very same item.
      - If no examples are returned, use a short natural name based on what the user said.
      - Use the user's own wording for the specific item (e.g. the name of the museum) whenever they provide it.
   - The `subscription_id` is optional. Provide it only when the user says the expense is covered by or tied to a travel subscription or pass - resolve it using subscription tools. **NEVER invent a fake UUID.**
   - After creating the expense, confirm with a concise summary stating the trip, description, type, and the value with its currency.
2. **Updating Trip Expense Description**
   - When the user asks to rename an expense or change its description, call `update_trip_expense_description`.
   - Always resolve the trip ID first using trip tools, then call `get_expenses` with that `trip_id` to find the target expense and its `id`. **NEVER ask the user for a UUID and NEVER invent one.**
   - If multiple expenses match the user's description, **ASK THE USER** to clarify which expense to update.
   - Follow the naming style of the existing expense descriptions (see `get_expense_descriptions`) and use the user's own wording for the specific item.
   - After updating the description, confirm with a concise summary stating the old and the new description.
3. **Updating Trip Expense Value and Currency**
   - When the user asks to change the value or the currency of an expense (or both), call `update_trip_expense_value_and_currency`.
   - Always resolve the trip ID first using trip tools, then call `get_expenses` with that `trip_id` to find the target expense and its `id`. **NEVER ask the user for a UUID and NEVER invent one.**
   - If multiple expenses match the user's description, **ASK THE USER** to clarify which expense to update.
   - The `value` is always required. When the user changes only the currency, ask whether the value stays the same, unless it is obvious from the request.
   - The `currency` is optional. Pass None when the user changes only the value, the current currency is kept.
   - The type and the subscription of an expense cannot be changed.
   - After updating the expense, confirm with a concise summary stating the old and the new value with its currency.
4. **Deleting Trip Expense**
   - When the user asks to delete, remove, or discard an expense, call `delete_trip_expense`.
   - Always resolve the trip ID first using trip tools, then call `get_expenses` with that `trip_id` to find the target expense and its `id`. **NEVER ask the user for a UUID and NEVER invent one.**
   - If multiple expenses match the user's description, **ASK THE USER** to clarify which expense to delete.
   - ALWAYS state which expense (description and value with its currency) is going to be deleted and **EXPLICITLY WAIT FOR USER CONFIRMATION** before calling the tool, as the deletion cannot be undone.
   - After deleting the expense, confirm with a concise summary stating the trip, description, and the value with its currency of the deleted expense.
