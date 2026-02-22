import CardGrid from "./CardGrid"
import PlaceCard from "./PlaceCard"

export default function PlaceCardGrid({ places, onPlaceRemoved }) {
    return (
        <CardGrid cardsPerRowCount={5}>
            {places?.sort((a, b) => a.distance - b.distance)?.map(place => (
                <PlaceCard
                    key={place.id}
                    place={place}
                    onPlaceRemoved={onPlaceRemoved} />
            ))}
        </CardGrid>
    )
}
