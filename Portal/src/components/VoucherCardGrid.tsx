import type { Voucher } from "../types/CoreSwaggerTypes.ts"
import CardGrid from "./CardGrid.tsx"
import VoucherCard from "./VoucherCard.tsx"

interface VoucherCardGridProps {
    vouchers: Voucher[] | null
    rowSize: number
    columnSize?: number
    onVoucherValueUpdated?: (voucherId: string, newValue: number) => Promise<Voucher>
    onVoucherRemoved?: (voucherId: string) => Promise<void>
}

export default function VoucherCardGrid({ vouchers, rowSize, columnSize, onVoucherValueUpdated, onVoucherRemoved }: VoucherCardGridProps) {
    return (
        <CardGrid
            rowSize={rowSize}
            columnSize={columnSize}>
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
