# Voucher Skill

## Purpose
Provides capabilities to query, view, and record vouchers, discount coupons, and gift cards in Travel Portal.

## Voucher Entity Structure
A voucher object contains:
- `code` (string): Promotional / redemption code (e.g. `"REB19M43HAMA"`, `"FLIX50"`).
- `issuer` (string): Company or provider (e.g. `"FLIXBUS"`, `"Airbnb"`, `"Booking.com"`, `"Ryanair"`).
- `value` (number): Remaining monetary value / balance.
- `currency` (string): 3-letter currency code (e.g. `"EUR"`, `"USD"`, `"CZK"`).
- `expiration` (string | null): Expiration date in ISO 8601 format with timezone offset (e.g. `"2025-07-31T23:59:59+0200"`).

## Voucher Guidelines & Actions
1. **Listing Vouchers**:
   - When the user asks about available vouchers, promo codes, or credits, call `get_vouchers()`.
   - Present voucher code, issuer, balance/currency, and expiration date clearly to the user in their local timezone (e.g. "31. 7. 2025"), ordered by expiration.
2. **Creating Vouchers**:
   - When the user asks to save, store, or log a voucher from raw text, emails, or messages, call `create_voucher(code, issuer, value, currency, expiration)`:
     - `code`: The voucher promo code string.
     - `issuer`: Name of the issuer / company (e.g. FLIXBUS, Ryanair, Booking.com, Airbnb).
     - `value`: Monetary amount.
     - `currency`: 3-letter currency code (uppercase).
     - `expiration`: Optional expiration date in ISO 8601 format with timezone offset (e.g. `"2025-12-31T23:59:59+0200"`). If not set, the voucher never expires.
   - **NEVER create provisional or placeholder vouchers with "Unknown" as issuer.**
   - **NEVER create vouchers with expiration in the past.**
   - **NEVER guess or invent an issuer** (e.g. do not assume "Booking.com" if not explicitly mentioned).
   - If the issuer is missing or uncertain, **ASK THE USER FIRST** before calling `create_voucher(code, issuer, value, currency, expiration)`.
   - After creating a voucher, confirm with a concise summary stating the issuer, value, currency, and code.
