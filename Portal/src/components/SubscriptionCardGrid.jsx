import CardGrid from "./CardGrid.tsx"
import SubscriptionCard from "./SubscriptionCard"

export default function SubscriptionCardGrid({ subscriptions, onSubscriptionRemoved }) {
    return (
        <CardGrid rowSize={5}>
            {subscriptions?.map(subscription => (
                <SubscriptionCard
                    key={subscription.id}
                    subscription={subscription}
                    onSubscriptionRemoved={onSubscriptionRemoved} />
            ))}
        </CardGrid>
    )
}
