import CardGrid from "./CardGrid"
import TripCard from "./TripCard"

export default function TripCardGrid({ trips, onTripRemoved }) {
    return (
        <CardGrid cardsPerRowCount={3}>
            {trips?.map(trip => (
                <TripCard
                    key={trip.id}
                    trip={trip}
                    onTripRemoved={onTripRemoved} />
            ))}
        </CardGrid>
    )
}
