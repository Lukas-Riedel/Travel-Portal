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
import { useParams } from "react-router-dom"

export default function Place() {
    const { id } = useParams()
    const [place, setPlace] = useState(null)
    const [places, setPlaces] = useState([])

    useEffect(() => {
        setPlace(null)
        setPlaces([])

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
            {place.getAlbums().length > 0 && place.getPastTrips().length === 0
                && <hr className="w-full h-0.5 bg-gradient-to-r from-transparent via-gray-400 to-transparent" />}
            <NearbyPlaceTileGrid
                place={place}
                places={places}
                count={3} />
        </div>
    )
}