import { useState, useEffect } from "react"
import PageHeader from "./PageHeader.jsx"
import HighlightCarousel from "./HighlightCarousel.jsx"
import CategoryBar from "./CategoryBar.jsx"
import LabelBar from "./LabelBar.jsx"
import DateTileGrid from "./DateTileGrid.jsx"
import TripBar from "./TripBar.jsx"
import { getPlace, listRegularPlaces } from "../util/api.js"
import PlaceContent from "./PlaceContent.jsx"
import NearbyPlaceTileGrid from "./NearbyPlaceTileGrid.jsx"

export default function Place({ id }) {
    const [place, setPlace] = useState(null)
    const [places, setPlaces] = useState([])

    useEffect(() => {
        getPlace(id)
            .then(setPlace)
            .catch(console.error)

        listRegularPlaces()
            .then(setPlaces)
            .catch(console.error)
    }, [id])

    if (!place) {
        return null
    }

    return (
        <div className="max-w-6xl mt-8 mb-8 rounded-2xl mx-auto p-8 bg-white text-gray-900">
            <PageHeader
                name={place.name}
                categories={[place.getMostSpecificCategoryWithMetadata()]} />
            <HighlightCarousel
                name={place.name}
                highlights={place.highlights} />
            <CategoryBar categories={place.categories} />
            <LabelBar labels={place.labels} />
            <PlaceContent place={place} />
            <DateTileGrid place={place} />
            <TripBar trips={place.getPastTrips()} />
            <NearbyPlaceTileGrid
                place={place}
                places={places}
                count={3} />
        </div>
    )
}