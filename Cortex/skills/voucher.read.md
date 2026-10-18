# Voucher Read Skill

## Purpose & Activation
Provides capabilities to query and view vouchers, discount coupons, and gift cards in Travel Portal.
Activate and apply this skill when the user asks about available vouchers, discount codes, or promotional credits (e.g. "what vouchers do I have?", "do I have any Flixbus or airline coupons?").

## Voucher Guidelines & Actions
1. **Listing Vouchers**
   - When the user asks about available vouchers, promo codes, or credits, call `get_vouchers`.
   - Present voucher code, issuer, balance/currency, and expiration date clearly to the user (e.g. "31. 7. 2025"), ordered by expiration.
