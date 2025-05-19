import { useState, useEffect } from "react"
import PageHeader from "../components/PageHeader.jsx"
import HighlightCarousel from "../components/HighlightCarousel.jsx"
import CategoryBar from "../components/CategoryBar.jsx"
import LabelBar from "../components/LabelBar.jsx"
import DateTileGrid from "../components/DateTileGrid.jsx"
import TripBar from "../components/TripBar.jsx"
import PlaceContent from "../components/PlaceContent.jsx"
import NearbyPlaceTileGrid from "../components/NearbyPlaceTileGrid.jsx"
import { useParams } from "react-router-dom"
import { useApi } from "../hooks/useApi.js"

export default function Place() {
    const { placeId } = useParams()
    const api = useApi()
    const [place, setPlace] = useState(null)
    const [places, setPlaces] = useState([])

    useEffect(() => {
        setPlace(null)
        setPlaces([])

        api.getPlace(placeId)
            .then(setPlace)
            .catch(console.error)

        api.listRegularPlaces()
            .then(setPlaces)
            .catch(console.error)
    }, [placeId])

    if (!place) {
        return null
    }

    return (
        <div>
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
                && <hr className="w-full h-0.5 my-4 bg-gradient-to-r from-transparent via-gray-400 to-transparent" />}
            <NearbyPlaceTileGrid
                place={place}
                places={places}
                count={3} />
        </div>
    )
}