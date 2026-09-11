import type { Place } from "../types/CoreSwaggerTypes.ts"
import type { Category } from "../types/CoreSwaggerTypes"
import PlaceTile from "./PlaceTile"
import TileGrid from "./TileGrid"

interface PlaceTileGridProps {
    places: Place[] | null
    placeMainCategorySelector?: (place: Place) => Category | null
}

export default function PlaceTileGrid({ places, placeMainCategorySelector }: PlaceTileGridProps) {
    return (
        <TileGrid>
            {places?.filter(place => place.mainHighlight)?.map(place => (
                <PlaceTile
                    key={place.id}
                    place={place}
                    mainCategory={placeMainCategorySelector?.(place) ?? undefined} />
            ))}
        </TileGrid>
    )
}