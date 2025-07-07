import PageHeader from "../components/PageHeader.jsx"
import HighlightCarousel from "../components/HighlightCarousel.jsx"
import CategoryBar from "../components/CategoryBar.jsx"
import LabelBar from "../components/LabelBar.jsx"
import DateTileGrid from "../components/DateTileGrid.jsx"
import TripBar from "../components/TripBar.jsx"
import PlaceContent from "../components/PlaceContent.jsx"
import NearbyPlaceTileGrid from "../components/NearbyPlaceTileGrid.jsx"
import { useParams } from "react-router-dom"
import SunAltitudeBar from "../components/SunAltitudeBar.jsx"
import { usePlace } from "../hooks/usePlace.js"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces.js"
import { useAuth } from "../contexts/AuthContext.jsx"

export default function PlacePage() {
    const { isAdmin } = useAuth()
    const { placeId } = useParams()

    const { place, updatePlaceName, updatePlaceAddress, removePlaceHighlight,
        updatePlaceMainHighlight, createPlaceLabel, removePlaceLabel, updatePlaceExcerpt,
        refreshPlaceExcerpt, updatePlaceLocation, refreshPlaceAlbum, updatePlaceHighlightQualityAttributes } = usePlace(placeId)
    const places = useTimeFilteredRegularPlaces({ include: "CATEGORIES", sort: "score" })

    return (
        <>
            <PageHeader
                name={place?.name}
                categories={place && [place.getCategory("MOST_SPECIFIC_WITH_METADATA")]}
                onNameChanged={updatePlaceName}
                onAddressChanged={updatePlaceAddress} />
            <HighlightCarousel
                place={place}
                highlights={place?.highlights}
                onHighlightRemoved={removePlaceHighlight}
                onMainHighlightUpdated={updatePlaceMainHighlight}
                onHighlightQualityAttributesUpdated={updatePlaceHighlightQualityAttributes} />
            <CategoryBar categories={place?.categories} />
            <LabelBar
                labels={place?.labels}
                onLabelAdded={createPlaceLabel}
                onLabelRemoved={removePlaceLabel} />
            <PlaceContent
                place={place}
                onExcerptChanged={updatePlaceExcerpt}
                onExcerptRefreshed={refreshPlaceExcerpt}
                onLocationChanged={updatePlaceLocation} />
            <DateTileGrid
                place={place}
                onAlbumRefreshed={refreshPlaceAlbum} />
            <TripBar trips={isAdmin ? place?.getAllTrips() : place?.getPastTrips()} />
            {place?.getAlbums().length > 0 && place.getPastTrips().length === 0
                && <hr className="w-full h-0.5 my-4 bg-gradient-to-r from-transparent via-gray-400 to-transparent" />}
            <NearbyPlaceTileGrid
                place={place}
                places={places}
                count={3} />
            <SunAltitudeBar place={place} />
        </>
    )
}