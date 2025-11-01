import { useQuery } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import { createVoucher, listVouchers, removeVoucher, updateVoucherValue } from "../clients/coreClient"

export const useVouchers = () => {
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listVouchers"],
        queryFn: listVouchers,
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 12
    })

    const refetchVouchers = _ => query.refetch()

    return {
        // TODO: Map to Statistics objects
        vouchers: query.data,
        createVoucher: (code, issuer, value, currency, expiration) => createVoucher(code, issuer, value, currency, expiration).then(refetchVouchers),
        updateVoucherValue: (voucherId, value) => updateVoucherValue(voucherId, value).then(refetchVouchers),
        removeVoucher: voucherId => removeVoucher(voucherId).then(refetchVouchers)
    }
}