import type { Voucher } from "./CoreSwaggerTypes.ts"

export interface UseVouchersResult {
    vouchers?: Voucher[]
    createVoucher: (code: string, issuer: string, value: number, currency: string, expiration?: number) => Promise<Voucher>
    updateVoucherValue: (voucherId: string, value: number) => Promise<Voucher>
    removeVoucher: (voucherId: string) => Promise<void>
}