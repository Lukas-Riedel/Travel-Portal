from typing import Any

from src.tools.base_tools import CoreApiTools


class VoucherTools(CoreApiTools):
    def get_tools(self) -> list[Any]:
        return [
            self.get_vouchers,
            self.create_voucher,
            self.update_voucher_value,
        ]

    def get_vouchers(self) -> list[dict[str, Any]]:
        """Retrieves all available vouchers and discount coupons stored in Travel Portal.
        Use this when the user asks about available vouchers, discount codes, or promotional credits
        (e.g. "what vouchers do I have?", "do I have any Flixbus or airline coupons?").

        Returns:
            A list of voucher objects, where each object contains:
            - id (string): Unique voucher identifier. You only need it to reference the voucher when updating its value.
            - code (string): The voucher code.
            - issuer (string): The issuer name.
            - value (number): Monetary value.
            - currency (string): Currency code.
            - expiration (string | null): ISO 8601 format with timezone offset (e.g. `"2025-12-31T23:59:59+0200"`) (if expiration was provided).
        """
        vouchers = self.core_client.get_vouchers()
        if isinstance(vouchers, dict) and vouchers.get("message"):
            return vouchers
        
        return [self._extract_voucher(v) for v in vouchers]

    def create_voucher(
        self,
        code: str,
        issuer: str,
        value: float,
        currency: str,
        expiration: int | None = None,
    ) -> dict[str, Any]:
        """Creates and stores a new voucher / gift card / discount coupon in Travel Portal.
        Use this when the user asks to save, record, or store a voucher code or coupon.

        Args:
            code: The unique code / alphanumeric voucher identifier (e.g. "SUMMER2025", "FLIX-9923-AZ").
            issuer: The issuer or service name (e.g. "FLIXBUS", "Airbnb", "Ryanair", "Booking.com").
            value: The monetary amount or value of the voucher (e.g. 50.0).
            currency: 3-letter currency code (e.g. "EUR", "USD", "CZK", "GBP").
            expiration: Optional expiration timestamp in Unix epoch seconds. Omit or pass None if no expiry.

        Returns:
            The created voucher object containing:
            - id (string): Unique voucher identifier. You only need it to reference the voucher when updating its value.
            - code (string): The voucher code.
            - issuer (string): The issuer name.
            - value (number): Monetary value.
            - currency (string): Currency code.
            - expiration (string | null): ISO 8601 format with timezone offset (e.g. `"2025-12-31T23:59:59+0200"`) (if expiration was provided).
        """
        voucher = self.core_client.create_voucher(
            code=code,
            issuer=issuer,
            value=value,
            currency=currency.upper(),
            expiration=expiration,
        )
        if isinstance(voucher, dict) and voucher.get("message"):
            return voucher

        return self._extract_voucher(voucher)

    def update_voucher_value(self, voucher_id: str, value: float) -> dict[str, Any]:
        """Updates the remaining value of an existing voucher / gift card / discount coupon in Travel Portal.
        Use this when the user says a voucher was (partially) used or its balance changed
        (e.g. "I used 20 EUR from my Flixbus voucher", "the Ryanair voucher now has 15 left").
        Only the value can be changed — code, issuer, currency and expiration cannot be edited.
        Always resolve the voucher first using get_vouchers — never ask the user for an ID or UUID.

        Args:
            voucher_id: The `id` of the voucher as returned by get_vouchers.
            value: The NEW total value of the voucher (not the difference), in the voucher's existing currency (e.g. 30.0).

        Returns:
            The updated voucher object containing:
            - id (string): Unique voucher identifier.
            - code (string): The voucher code.
            - issuer (string): The issuer name.
            - value (number): Monetary value.
            - currency (string): Currency code.
            - expiration (string | null): ISO 8601 format with timezone offset (e.g. `"2025-12-31T23:59:59+0200"`) (if expiration was provided).
        """
        voucher = self.core_client.update_voucher(voucher_id=voucher_id, value=value)
        if isinstance(voucher, dict) and voucher.get("message"):
            return voucher

        return self._extract_voucher(voucher)

    def _extract_voucher(self,voucher: dict[str, Any]) -> dict[str, Any]:
        return {
            "id": voucher.get("id"),
            "code": voucher.get("code"),
            "issuer": voucher.get("issuer"),
            "value": voucher.get("value"),
            "currency": voucher.get("currency"),
            "expiration": self._epoch_to_iso(voucher.get("expiration"), self._get_user_timezone()),
        }
