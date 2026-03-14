
import { Diff, Trash2 } from "lucide-react"
import LoadingCard from "./LoadingCard.tsx"
import { getDateString } from "../utils/helpers"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"

export default function VoucherCard({ voucher, onVoucherValueUpdated, onVoucherRemoved }) {
    const { showRemoveVoucherToast, showSubtractVoucherValueToast } = usePredefinedUserInput()

    const handleDelete = () => {
        showRemoveVoucherToast(() => onVoucherRemoved(voucher.id))
    }

    const handleValueSubtraction = () => {
        showSubtractVoucherValueToast(value => onVoucherValueUpdated(voucher.id, voucher.value - value))
    }

    const voucherProperties = {
        "Identifikátor": voucher.code,
        "Hodnota": `${voucher.value} ${voucher.currency}`,
        "Expirace": getDateString(voucher.expiration)
    }

    return voucher ? (
        <div className="bg-white rounded-xl shadow-md max-w-xl mx-auto p-3 w-full space-y-1">
            <div className="flex justify-start items-center">
                <span className="text-lg font-semibold">
                    {voucher.issuer}
                </span>
                {!!(onVoucherValueUpdated || onVoucherRemoved) && (
                    <ul className="flex justify-end gap-1 ml-auto">
                        {onVoucherValueUpdated && (
                            <li>
                                <button
                                    onClick={handleValueSubtraction}
                                    className="p-1 rounded text-orange-600 hover:bg-gray-100 transition-colors">
                                    <Diff size={16} />
                                </button>
                            </li>
                        )}
                        {onVoucherRemoved && (
                            <li>
                                <button
                                    onClick={handleDelete}
                                    className="p-1 rounded text-red-800 hover:bg-gray-100 transition-colors">
                                    <Trash2 size={16} />
                                </button>
                            </li>
                        )}
                    </ul>
                )}
            </div>
            <ul className="space-y-0.5 mt-2">
                {Object.entries(voucherProperties).filter(([key]) => voucherProperties[key]).map(([key, value]) => (
                    <li
                        key={key}
                        className="text-gray-700">
                        <span className="font-semibold">
                            {key}:
                        </span>
                        {" "}
                        <span dangerouslySetInnerHTML={{ __html: value }} />
                    </li>
                ))}
            </ul>
        </div>
    ) : (
        <LoadingCard />
    )
}
