import { useState, useEffect } from "react"
import { formatKilometers } from "../utils/formatters.js"
import Place from "../model/place.js"
import TileGrid from "./TileGrid.jsx"
import PlaceTile from "./PlaceTile.jsx"
import { TailSpin } from "react-loader-spinner"
import { useApi } from "../hooks/useApi.js"

export default function NearbyPlaceTileGrid({ place, places, count }) {
    const [nearbyPlaces, setNearbyPlaces] = useState(null)
    const api = useApi()

    useEffect(() => {
        setNearbyPlaces(null)

        const approximatedNearbyPlaces = places
            .filter(p => p.id !== place.id)
            .filter(p => p.mainHighlight)
            .map(p => new Place({ ...p, distance: p.getEuclideanDistanceTo(place) }))
        approximatedNearbyPlaces.sort((a, b) => a.distance - b.distance)

        const nearbyPlaces = approximatedNearbyPlaces
            .slice(0, 2 * count)
            .map(p => new Place({ ...p, distance: p.getHaversineDistanceTo(place) }))
        nearbyPlaces.sort((a, b) => a.distance - b.distance)

        Promise.all(nearbyPlaces
            .slice(0, count)
            .map(p => api.getPlace(p.id)))
            .then(setNearbyPlaces)
    }, [place, places, count])

    if (!nearbyPlaces) {
        return (
            <TileGrid tiles={Array.from({ length: count }, (_, index) => (
                <div
                    key={index}
                    className="relative w-[350px] h-[233px] mx-auto flex items-center justify-center">
                    <TailSpin
                        color="black"
                        height={30}
                        width={30}
                    />
                </div>
            ))} />
        )
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