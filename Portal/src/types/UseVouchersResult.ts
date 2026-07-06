import type { ExpenseCurrency, Voucher } from "./CoreSwaggerTypes.ts"

export interface UseVouchersResult {
    vouchers?: Voucher[]
    createVoucher: (code: string, issuer: string, value: number, currency: ExpenseCurrency, expiration?: number) => Promise<Voucher>
    updateVoucherValue: (voucherId: string, value: number) => Promise<Voucher>
    removeVoucher: (voucherId: string) => Promise<void>
}