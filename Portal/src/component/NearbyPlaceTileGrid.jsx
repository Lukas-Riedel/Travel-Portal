import { useState, useEffect } from "react"
import { formatKilometers } from "../util/formatters.js"
import Place from "../model/place"
import TileGrid from "./TileGrid.jsx"
import PlaceTile from "./PlaceTile.jsx"
import { getPlace } from "../util/api.js"

export default function NearbyPlaceTileGrid({ place, places, count }) {
    const [nearbyPlaces, setNearbyPlaces] = useState(null)

    useEffect(() => {
        const approximatedNearbyPlaces = places
            .filter(p => p.id !== place.id)
            .map(p => new Place({ ...p, distance: p.getEuclideanDistanceTo(place) }))
        approximatedNearbyPlaces.sort((a, b) => a.distance - b.distance)

        const nearbyPlaces = approximatedNearbyPlaces
            .slice(0, 2 * count)
            .filter(p => p.id !== place.id)
            .map(p => new Place({ ...p, distance: p.getHaversineDistanceTo(place) }))
        nearbyPlaces.sort((a, b) => a.distance - b.distance)

        Promise.all(nearbyPlaces
            .slice(0, count)
            .map(p => getPlace(p.id)))
            .then(setNearbyPlaces)
    }, [place, places, count])

    if (!nearbyPlaces) {
        return null
    }

    return (
        <TileGrid tiles={nearbyPlaces.map((nearbyPlace, index) => (
            <PlaceTile
                key={index}
                place={nearbyPlace}
                secondLineText={formatKilometers(nearbyPlace.getHaversineDistanceTo(place).toFixed(0))} />
        ))} />
    )
}