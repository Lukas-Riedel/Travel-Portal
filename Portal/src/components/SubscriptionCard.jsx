
import { Trash2 } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import showConfirmToast from "./ConfirmToast"
import LoadingCard from "./LoadingCard"
import { getDateString } from "../utils/helpers"

export default function SubscriptionCard({ subscription, onSubscriptionRemoved }) {
    const { isAdmin } = useAuth()

    const handleDelete = () => {
        showConfirmToast(
            "Opravdu chceš odstranit předplatné '" + subscription.description + "'?",
            "Předplatné bylo úspěšně odstraněno",
            "Nepodařilo se odstranit předplatní",
            async () => onSubscriptionRemoved(subscription.id)
        )
    }

    const subscriptionProperties = {
        "Hodnota": `${subscription.value} ${subscription.currency}`,
        "Expirace": getDateString(subscription.expiration),
        "Počet použití": subscription.occurrences
    }

    return subscription ? (
        <div className="bg-white rounded-xl shadow-md max-w-xl mx-auto p-3 w-full space-y-1">
            <div className="flex justify-start items-center">
                <span className="text-lg font-semibold">
                    {subscription.description}
                </span>
                {isAdmin && onSubscriptionRemoved && (
                    <button
                        onClick={() => handleDelete(subscription)}
                        className="p-1 rounded text-red-800 hover:bg-gray-100 transition-colors ml-auto">
                        <Trash2 size={16} />
                    </button>
                )}
            </div>
            <ul className="space-y-0.5 mt-2">
                {Object.entries(subscriptionProperties).filter(([key]) => subscriptionProperties[key]).map(([key, value]) => (
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
