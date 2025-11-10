import type { Voucher } from "./CoreSwaggerTypes.ts"

export interface UseVouchersResult {
    vouchers?: Voucher[]
    createVoucher: (code: string, issuer: string, value: number, currency: string, expiration?: number) => Promise<void>
    updateVoucherValue: (voucherId: string, value: number) => Promise<void>
    removeVoucher: (voucherId: string) => Promise<void>
}