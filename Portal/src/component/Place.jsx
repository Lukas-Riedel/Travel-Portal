import { useState, useEffect } from "react"

import Map from "./Map.jsx"
import PageHeader from "./PageHeader.jsx"
import HighlightCarousel from "./HighlightCarousel.jsx"
import CategoryBar from "./CategoryBar.jsx"
import LabelBar from "./LabelBar.jsx"
import DateTileGrid from "./DateTileGrid.jsx"
import TripBar from "./TripBar.jsx"
import PlaceTile from "./PlaceTile.jsx"

import { getMostSpecificCategoryWithMetadata, getDistance } from "../util/helpers.js"
import { getPlace, listRegularPlaces } from "../util/api.js"
import { formatKilometers } from "../util/formatters.js"

export default function Place({ id }) {
    const [place, setPlace] = useState(null)
    const [nearbyPlaces, setNearbyPlaces] = useState([])

    useEffect(() => {
        Promise.all([getPlace(id), listRegularPlaces()])
            .then(([place, places]) => {
                setPlace(place)
                const nearbyPlaces = places
                    .filter(p => p.id !== place.id)
                    .map(p => ({ ...p, distance: getDistance(p, place) }))
                nearbyPlaces.sort((a, b) => a.distance - b.distance)
                Promise.all(nearbyPlaces.slice(0, 3).map(p => getPlace(p.id)))
                    .then(setNearbyPlaces)
            })
            .catch(console.error)
    }, [])

    if (!place) {
        return null
    }

    const mostSpecificCategory = getMostSpecificCategoryWithMetadata(place)
    const trips = [...new globalThis.Map(
        place.dates
            .map(date => date.trip)
            .filter(trip => trip !== null)
            .map(trip => [trip.id, trip]))
        .values()]

    return (
        <div className="max-w-6xl mt-8 mb-8 rounded-2xl mx-auto p-8 bg-white text-gray-900">
            <PageHeader
                name={place.name}
                categories={[mostSpecificCategory]} />
            <HighlightCarousel
                name={place.name}
                highlights={place.highlights} />
            <CategoryBar categories={place.categories} />
            <LabelBar labels={place.labels} />

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                <div>
                    <p className="text-gray-700 text-justify leading-relaxed">
                        {place.excerpt}
                    </p>
                </div>

                <div className="w-full h-full overflow-hidden rounded-lg shadow">
                    <Map
                        zoom={8}
                        latitude={place.latitude}
                        longitude={place.longitude}
                        color={mostSpecificCategory.metadata.color} />
                </div>
            </div>

            <DateTileGrid place={place} />
            {trips.length > 0
                ? <TripBar trips={trips} />
                : <hr className="w-full h-0.5 bg-gradient-to-r from-transparent via-gray-400 to-transparent" />}

            {nearbyPlaces.length > 0 && (
                <div
                    className="grid gap-4 justify-center my-4"
                    style={{ gridTemplateColumns: "repeat(auto-fit, minmax(350px, 1fr))" }}>
                    {nearbyPlaces.map((p, index) => (
                        <PlaceTile
                            key={index}
                            place={p}
                            secondLineText={formatKilometers(getDistance(p, place).toFixed(0))} />
                    ))}
                </div>)}
        </div>
    )
}