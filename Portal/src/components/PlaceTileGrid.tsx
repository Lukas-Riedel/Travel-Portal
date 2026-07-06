import TileGrid from "./TileGrid"
import PlaceTile from "./PlaceTile"
import type { Category } from "../types/CoreSwaggerTypes"
import type { Place } from "../classes/Place"

interface PlaceTileGridProps {
    places: Place[] | null
    placeMainCategorySelector: (place: Place) => Category | undefined
}

export default function PlaceTileGrid({ places, placeMainCategorySelector }: PlaceTileGridProps) {
    return (
        <TileGrid>
            {places?.filter(place => place.mainHighlight)?.map(place => (
                <PlaceTile
                    key={place.id}
                    place={place}
                    mainCategory={placeMainCategorySelector(place)} />
            ))}
        </TileGrid>
    )
}