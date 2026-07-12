import type { Place } from "../classes/Place"
import { useAppNavigate } from "../hooks/useAppNavigate"
import type { Category } from "../types/CoreSwaggerTypes"
import Map from "./Map"

interface PlaceMapProps {
    places: Place[] | null
    placeMainCategorySelector: (place: Place) => Category
    onRightClick?: (latitude: number, longitude: number) => Promise<void>
}

export default function PlaceMap({ places, placeMainCategorySelector, onRightClick }: PlaceMapProps) {
    const navigate = useAppNavigate()

    return (
        <Map
            points={places?.map(place => ({
                name: place.name,
                latitude: place.latitude,
                longitude: place.longitude,
                color: placeMainCategorySelector(place)?.metadata?.color,
                unicode: placeMainCategorySelector(place)?.metadata?.unicode,
                onClick: () => Promise.resolve(navigate(place))
            }))}
            onRightClick={onRightClick} />
    )
}