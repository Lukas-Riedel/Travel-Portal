import type { Subscription } from "../types/CoreSwaggerTypes.ts"
import CardGrid from "./CardGrid.tsx"
import SubscriptionCard from "./SubscriptionCard.tsx"

interface SubscriptionCardGridProps {
    subscriptions: Subscription[] | null
    rowSize: number
    onSubscriptionRemoved?: (subscriptionId: string) => Promise<void>
}

export default function SubscriptionCardGrid({ subscriptions, rowSize, onSubscriptionRemoved }: SubscriptionCardGridProps) {
    return (
        <CardGrid rowSize={rowSize}>
            {subscriptions?.map(subscription => (
                <SubscriptionCard
                    key={subscription.id}
                    subscription={subscription}
                    onSubscriptionRemoved={onSubscriptionRemoved} />
            ))}
        </CardGrid>
    )
}
