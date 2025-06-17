import { useMemo } from "react"
import { formatKilometers } from "../utils/formatters.js"
import Place from "../model/place.js"
import TileGrid from "./TileGrid.jsx"
import PlaceTile from "./PlaceTile.jsx"

export default function NearbyPlaceTileGrid({ place, places, count }) {
    const nearbyPlaces = useMemo(() => places
        ?.filter(p => p.id !== place?.id)
        ?.filter(p => p?.mainHighlight)
        ?.map(p => place && new Place({ ...p, distance: p.getEuclideanDistanceTo(place) }))
        ?.filter(Boolean)
        ?.sort((a, b) => a.distance - b.distance)
        ?.slice(0, 2 * count)
        ?.map(p => place && new Place({ ...p, distance: p.getHaversineDistanceTo(place) }))
        ?.filter(Boolean)
        ?.sort((a, b) => a.distance - b.distance)
        ?.slice(0, count), [place, places, count])

    return (
        <TileGrid>
            {nearbyPlaces?.map((nearbyPlace, index) => (
                <PlaceTile
                    key={index}
                    place={nearbyPlace}
                    mainCategory={nearbyPlace?.getCategory("MOST_SPECIFIC_WITH_METADATA")}
                    secondLineText={formatKilometers(Math.round(nearbyPlace?.getHaversineDistanceTo(place)))} />
            ))}
        </TileGrid>
    )
}