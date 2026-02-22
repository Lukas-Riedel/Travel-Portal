import CardGrid from "./CardGrid"
import SubscriptionCard from "./SubscriptionCard"

export default function SubscriptionCardGrid({ subscriptions, onSubscriptionRemoved }) {
    return (
        <CardGrid cardsPerRowCount={5}>
            {subscriptions?.map(subscription => (
                <SubscriptionCard
                    key={subscription.id}
                    subscription={subscription}
                    onSubscriptionRemoved={onSubscriptionRemoved} />
            ))}
        </CardGrid>
    )
}
