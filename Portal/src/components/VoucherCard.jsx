
import { Diff, Trash2 } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import { useUserInput } from "../hooks/useUserInput.tsx"
import LoadingCard from "./LoadingCard"
import { getDateString } from "../utils/helpers"

export default function VoucherCard({ voucher, onVoucherValueUpdated, onVoucherRemoved }) {
    const { isAdmin } = useAuth()
    const { showConfirmToast, showFormToast } = useUserInput()

    const handleDelete = () => {
        showConfirmToast(
            "Opravdu chceš odstranit poukaz '" + voucher.description + "'?",
            async () => onVoucherRemoved(voucher.id),
            "Poukaz byl úspěšně odstraněn",
            "Nepodařilo se odstranit poukaz"
        )
    }

    const handleValueSubtraction = () => {
        showFormToast(
            "Zadej, o kolik se má snížit hodnota poukazu:",
            [
                { defaultValue: 0, required: true, type: "number", min: 0 }
            ],
            async (value) => onVoucherValueUpdated(voucher.id, voucher.value - value),
            "Hodnota poukazu byla úspěšně aktualizována",
            "Nepodařilo se aktualizovat hodnotu poukazu"
        )
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
                {isAdmin && (
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
