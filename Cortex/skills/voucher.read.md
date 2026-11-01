# Voucher Read Skill

1. **Listing Vouchers**
   - When the user asks about available vouchers, promo codes, or credits, call `get_vouchers`.
   - Present voucher code, issuer, balance/currency, and expiration date clearly to the user (e.g. "31. 7. 2025"). Vouchers are ordered by issuer and then by expiration date.
2. **Extracting Voucher ID**
   - Once the target voucher is found, use its `id` for subsequent actions. **NEVER invent a fake UUID.**
