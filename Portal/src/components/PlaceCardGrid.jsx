import CardGrid from "./CardGrid.tsx"
import PlaceCard from "./PlaceCard"

export default function PlaceCardGrid({ places, onPlaceRemoved }) {
    return (
        <CardGrid rowSize={5}>
            {places?.sort((a, b) => a.distance - b.distance)?.map(place => (
                <PlaceCard
                    key={place.id}
                    place={place}
                    onPlaceRemoved={onPlaceRemoved} />
            ))}
        </CardGrid>
    )
}
