import Map from "./Map.jsx"

export default function PlaceMap({ places, onRightClick }) {
    return (
        <Map
            points={places.map(place => {
                return {
                    name: place.name,
                    latitude: place.latitude,
                    longitude: place.longitude,
                    color: place.getMostSpecificCategoryWithMetadata().metadata.color,
                    onClick: () => window.location.href = "/place/" + place.id
                }
            })}
            onRightClick={onRightClick} />
    )
}