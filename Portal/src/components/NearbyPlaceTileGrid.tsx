import { useFormatters } from "../hooks/useFormatters.ts"
import type { Place } from "../types/CoreSwaggerTypes.ts"
import { InternalCategoryCategory } from "../types/InternalCategoryCategory.ts"
import { getHaversineDistance } from "../utils/geocodingUtils.ts"
import { getPlaceCategory } from "../utils/placeUtils.ts"
import PlaceTile from "./PlaceTile"
import TileGrid from "./TileGrid"

interface NearbyPlaceTileGridProps {
    place: Place | null
}

export default function NearbyPlaceTileGrid({ place }: NearbyPlaceTileGridProps) {
    const { formatKilometers } = useFormatters()
    return (
        <TileGrid>
            {place?.nearbyPlaces?.filter(nearbyPlace => nearbyPlace.mainHighlight)?.map(nearbyPlace => (
                <PlaceTile
                    key={nearbyPlace.id}
                    place={nearbyPlace}
                    mainCategory={getPlaceCategory(nearbyPlace, InternalCategoryCategory.MostSpecificWithMetadata) ?? undefined}
                    secondLineText={formatKilometers(Math.round(getHaversineDistance(nearbyPlace, place!)))} />
            ))}
        </TileGrid>
    )
}