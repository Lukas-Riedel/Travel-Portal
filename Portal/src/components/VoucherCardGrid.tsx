import type { Voucher } from "../types/CoreSwaggerTypes.ts"
import CardGrid from "./CardGrid.tsx"
import VoucherCard from "./VoucherCard.tsx"

interface VoucherCardGridProps {
    vouchers: Voucher[] | null
    rowSize: number
    onVoucherValueUpdated?: (voucherId: string, newValue: number) => Promise<Voucher>
    onVoucherRemoved?: (voucherId: string) => Promise<void>
}

export default function VoucherCardGrid({ vouchers, rowSize, onVoucherValueUpdated, onVoucherRemoved }: VoucherCardGridProps) {
    return (
        <CardGrid rowSize={rowSize}>
            {vouchers?.map(voucher => (
                <VoucherCard
                    key={voucher.id}
                    voucher={voucher}
                    onVoucherValueUpdated={onVoucherValueUpdated}
                    onVoucherRemoved={onVoucherRemoved} />
            ))}
        </CardGrid>
    )
}
