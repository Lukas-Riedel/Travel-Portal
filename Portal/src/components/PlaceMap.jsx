import Map from "./Map.jsx"

export default function PlaceMap({ places, placeMainCategorySelector, onRightClick }) {
    return (
        <Map
            points={places?.map(place => {
                return {
                    name: place?.name,
                    latitude: place?.latitude,
                    longitude: place?.longitude,
                    color: placeMainCategorySelector(place)?.metadata?.color,
                    onClick: () => window.location.href = (window.location.pathname.startsWith("/plan") ? "/plan/place/" : "/place/") + place?.id
                }
            })}
            onRightClick={onRightClick} />
    )
}