import { createVoucher, listVouchers, removeVoucher, updateVoucherValue } from "../clients/coreClient.ts"
import type { UseVouchersResult } from "../types/UseVouchersResult.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const useVouchers = (): UseVouchersResult => {
    const { response, refetchResponse } = useQuery({
        queryKey: ["listVouchers"],
        queryFn: listVouchers,
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return {
        vouchers: response,
        createVoucher: (code: string, issuer: string, value: number, currency: string, expiration?: number) => createVoucher(code, issuer, value, currency, expiration).then(refetchResponse),
        updateVoucherValue: (voucherId: string, value: number) => updateVoucherValue(voucherId, value).then(refetchResponse),
        removeVoucher: (voucherId: string) => removeVoucher(voucherId).then(refetchResponse)
    }
}