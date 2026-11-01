# Voucher Edit Skill

1. **Creating Vouchers**
   - When the user asks to save, store, or log a voucher from raw text, emails, or messages, call `create_voucher`.
   - Expiration is optional. If the user provides an expiration date, use `convert_datetime_to_epoch` with the user's current timezone from Environmental Context to convert it. If there is no expiration date, pass None.
   - **NEVER create vouchers with expiration in the past.**
   - **NEVER guess or invent an issuer** (e.g. do not assume "Booking.com" if not explicitly mentioned).
   - If the issuer is missing or uncertain, **ASK THE USER FIRST** before calling `create_voucher`.
   - After creating a voucher, confirm with a concise summary stating the issuer, value, currency, and code.
2. **Updating Voucher Value**
   - When the user says a voucher was used (fully or partially) or its balance changed, call `update_voucher_value`.
   - Always find the voucher first via `get_vouchers` and use its `id`. **NEVER ask the user for a UUID and NEVER invent one.**
   - If more vouchers match the user's description (e.g. several from the same issuer), **ASK THE USER** which one before updating.
   - The `value` is the NEW total remaining value, not the difference. If the user gives the amount spent, subtract it from the current value yourself. **NEVER set a negative value.**
   - Only the value can be changed. Code, issuer, currency, and expiration cannot be edited.
   - After updating, confirm with a concise summary stating the issuer, code, old value, new value, and currency.
