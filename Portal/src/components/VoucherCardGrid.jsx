import CardGrid from "./CardGrid.tsx"
import VoucherCard from "./VoucherCard"

export default function VoucherCardGrid({ vouchers, onVoucherValueUpdated, onVoucherRemoved }) {
    return (
        <CardGrid rowSize={4}>
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
