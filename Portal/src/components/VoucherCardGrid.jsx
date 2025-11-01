import CardGrid from "./CardGrid"
import VoucherCard from "./VoucherCard"

export default function VoucherCardGrid({ vouchers, onVoucherRemoved }) {
    return (
        <CardGrid cardsPerRowCount={4}>
            {vouchers?.map(voucher => (
                <VoucherCard
                    key={voucher.id}
                    voucher={voucher}
                    onVoucherRemoved={onVoucherRemoved} />
            ))}
        </CardGrid>
    )
}
