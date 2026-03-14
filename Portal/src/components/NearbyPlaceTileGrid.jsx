import TileGrid from "./TileGrid.jsx"
import PlaceTile from "./PlaceTile.jsx"
import { Place } from "../classes/Place.ts"

export default function NearbyPlaceTileGrid({ place }) {
    const { formatKilometers } = useFormatters()
    return (
        <TileGrid>
            {place?.nearbyPlaces?.map(nearbyPlace => new Place(nearbyPlace))?.filter(nearbyPlace => nearbyPlace.mainHighlight)?.map(nearbyPlace => (
                <PlaceTile
                    key={nearbyPlace.id}
                    place={nearbyPlace}
                    mainCategory={nearbyPlace?.getCategory("mostSpecificWithMetadata")}
                    secondLineText={formatKilometers(Math.round(nearbyPlace?.getHaversineDistanceTo(place)))} />
            ))}
        </TileGrid>
    )
}