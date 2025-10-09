import CardGrid from "./CardGrid"
import SubscriptionCard from "./SubscriptionCard"

export default function SubscriptionCardGrid({ subscriptions, onSubscriptionRemoved }) {
    return (
        <CardGrid cardsPerRowCount={5}>
            {subscriptions?.sort((a, b) => a.distance - b.distance)?.map(subscription => (
                <SubscriptionCard
                    key={subscription.id}
                    subscription={subscription}
                    onSubscriptionRemoved={onSubscriptionRemoved} />
            ))}
        </CardGrid>
    )
}
