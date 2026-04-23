import { useMemo } from "react"
import { Trash2 } from "lucide-react"
import { useTranslation } from "react-i18next"
import LoadingCard from "./LoadingCard.tsx"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import type { Subscription } from "../types/CoreSwaggerTypes.ts"
import Card from "./Card.tsx"
import PropertyCardContent from "./PropertyCardContent.tsx"
import { formatTimestamp } from "../utils/timeUtils.ts"

interface SubscriptionCardProps {
    subscription: Subscription | null
    onSubscriptionRemoved?: (subscriptionId: string) => Promise<void>
}

export default function SubscriptionCard({ subscription, onSubscriptionRemoved }: SubscriptionCardProps) {
    const { t } = useTranslation()
    const { showRemoveSubscriptionToast } = usePredefinedUserInput()

    const handleDelete = () => {
        if (onSubscriptionRemoved) {
            showRemoveSubscriptionToast(() => onSubscriptionRemoved(subscription.id))
        }
    }

    const properties = useMemo(() => subscription && ({
        [t("subscription.label.value")]: `${subscription.value} ${subscription.currency}`,
        [t("subscription.label.expiration")]: formatTimestamp(subscription.expiration, t("general.format.date.year.included")),
        [t("subscription.label.occurrences")]: subscription.occurrences
    }), [subscription, t])

    if (!subscription) {
        return (
            <LoadingCard />
        )
    }

    return (
        <Card>
            <div className="flex justify-start items-center">
                <span className="text-lg font-semibold">
                    {subscription.description}
                </span>
                {onSubscriptionRemoved && (
                    <button
                        onClick={handleDelete}
                        className="p-1 rounded text-red-800 hover:bg-gray-100 transition-colors ml-auto">
                        <Trash2 size={16} />
                    </button>
                )}
            </div>
            <PropertyCardContent properties={properties} />
        </Card>
    )
}
