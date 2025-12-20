import { formatKilometers } from "../utils/formatters.js"
import TileGrid from "./TileGrid.jsx"
import PlaceTile from "./PlaceTile.jsx"
import { Place } from "../classes/Place.ts"

export default function NearbyPlaceTileGrid({ place }) {
    return (
        <TileGrid>
            {place?.nearbyPlaces?.map(nearbyPlace => new Place(nearbyPlace))?.map(nearbyPlace => (
                <PlaceTile
                    key={nearbyPlace.id}
                    place={nearbyPlace}
                    mainCategory={nearbyPlace?.getCategory("mostSpecificWithMetadata")}
                    secondLineText={formatKilometers(Math.round(nearbyPlace?.getHaversineDistanceTo(place)))} />
            ))}
        </TileGrid>
    )
}