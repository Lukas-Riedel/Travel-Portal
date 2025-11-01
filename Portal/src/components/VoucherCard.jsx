
import { Trash2 } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import showConfirmToast from "./ConfirmToast"
import LoadingCard from "./LoadingCard"
import { getDateString } from "../utils/helpers"

export default function VoucherCard({ voucher, onVoucherRemoved }) {
    const { isAdmin } = useAuth()

    const handleDelete = () => {
        showConfirmToast(
            "Opravdu chceš odstranit poukaz '" + voucher.description + "'?",
            "Poukaz byl úspěšně odstraněn",
            "Nepodařilo se odstranit poukaz",
            async () => onVoucherRemoved(voucher.id)
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
                {isAdmin && onVoucherRemoved && (
                    <button
                        onClick={() => handleDelete(voucher)}
                        className="p-1 rounded text-red-800 hover:bg-gray-100 transition-colors ml-auto">
                        <Trash2 size={16} />
                    </button>
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
