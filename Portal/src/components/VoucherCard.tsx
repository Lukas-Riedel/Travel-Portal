import { Diff, Trash2 } from "lucide-react"
import { useMemo } from "react"
import { useTranslation } from "react-i18next"

import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import type { Voucher } from "../types/CoreSwaggerTypes.ts"
import { formatTimestamp } from "../utils/timeUtils.ts"
import Card from "./Card.tsx"
import LoadingCard from "./LoadingCard.tsx"
import PropertyCardContent from "./PropertyCardContent.tsx"

interface VoucherCardProps {
    voucher: Voucher | null
    onVoucherValueUpdated?: (voucherId: string, newValue: number) => Promise<Voucher>
    onVoucherRemoved?: (voucherId: string) => Promise<void>
}

export default function VoucherCard({ voucher, onVoucherValueUpdated, onVoucherRemoved }: VoucherCardProps) {
    const { t } = useTranslation()
    const { showRemoveVoucherToast, showSubtractVoucherValueToast } = usePredefinedUserInput()

    const handleVoucherRemoved = () => {
        if (voucher && onVoucherRemoved) {
            showRemoveVoucherToast(() => onVoucherRemoved(voucher.id))
        }
    }

    const handleVoucherValueSubtracted = () => {
        if (voucher && onVoucherValueUpdated) {
            showSubtractVoucherValueToast(value => onVoucherValueUpdated(voucher.id, voucher.value - value))
        }
    }

    const properties = useMemo(() => voucher && ({
        [t("voucher.label.code")]: voucher.code,
        [t("voucher.label.value")]: `${voucher.value} ${voucher.currency}`,
        [t("voucher.label.expiration")]: voucher.expiration && formatTimestamp(voucher.expiration, t("general.format.date.year.included"))
    }), [voucher, t])

    if (!voucher) {
        return (
            <LoadingCard />
        )
    }

    return (
        <Card>
            <div className="flex justify-start items-center">
                <span className="text-lg font-semibold">
                    {voucher.issuer}
                </span>
                {!!(onVoucherValueUpdated || onVoucherRemoved) && (
                    <ul className="flex justify-end gap-1 ml-auto">
                        {onVoucherValueUpdated && (
                            <li>
                                <button
                                    onClick={handleVoucherValueSubtracted}
                                    className="btn-ghost-warning">
                                    <Diff size={16} />
                                </button>
                            </li>
                        )}
                        {onVoucherRemoved && (
                            <li>
                                <button
                                    onClick={handleVoucherRemoved}
                                    className="btn-ghost-danger">
                                    <Trash2 size={16} />
                                </button>
                            </li>
                        )}
                    </ul>
                )}
            </div>
            <PropertyCardContent properties={properties} />
        </Card>
    )
}