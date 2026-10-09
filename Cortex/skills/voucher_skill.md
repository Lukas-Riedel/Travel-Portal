# Voucher Skill

## Purpose
Provides capabilities to query, view, and record vouchers, discount coupons, and gift cards in Travel Portal.

## Voucher Guidelines & Actions
1. **Listing Vouchers**:
   - When the user asks about available vouchers, promo codes, or credits, call `get_vouchers`.
   - Present voucher code, issuer, balance/currency, and expiration date clearly to the user (e.g. "31. 7. 2025"), ordered by expiration.
2. **Creating Vouchers**:
   - When the user asks to save, store, or log a voucher from raw text, emails, or messages, call `create_voucher`. For `expiration`, use `convert_datetime_to_epoch` with the user's current timezone from Environmental Context as `tz_name`. If no expiry, omit the argument.
   - **NEVER create provisional or placeholder vouchers with "Unknown" as issuer.**
   - **NEVER create vouchers with expiration in the past.**
   - **NEVER guess or invent an issuer** (e.g. do not assume "Booking.com" if not explicitly mentioned).
   - If the issuer is missing or uncertain, **ASK THE USER FIRST** before calling `create_voucher`.
   - After creating a voucher, confirm with a concise summary stating the issuer, value, currency, and code.
