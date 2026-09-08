import { Place } from "../classes/Place.ts"
import { useFormatters } from "../hooks/useFormatters.ts"
import { InternalCategoryCategory } from "../types/InternalCategoryCategory.ts"
import PlaceTile from "./PlaceTile"
import TileGrid from "./TileGrid"

interface NearbyPlaceTileGridProps {
    place: Place | null
}

export default function NearbyPlaceTileGrid({ place }: NearbyPlaceTileGridProps) {
    const { formatKilometers } = useFormatters()
    return (
        <TileGrid>
            {place?.nearbyPlaces?.map(nearbyPlace => new Place(nearbyPlace))?.filter(nearbyPlace => nearbyPlace.mainHighlight)?.map(nearbyPlace => (
                <PlaceTile
                    key={nearbyPlace.id}
                    place={nearbyPlace}
                    mainCategory={nearbyPlace.getCategory(InternalCategoryCategory.MostSpecificWithMetadata) ?? undefined}
                    secondLineText={formatKilometers(Math.round(nearbyPlace.getHaversineDistanceTo(place)))} />
            ))}
        </TileGrid>
    )
}