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
import SunAltitudeBar from "../components/SunAltitudeBar.jsx"
import { useRegularPlaces } from "../hooks/useRegularPlaces.js"
import { usePlace } from "../hooks/usePlace.js"
import { getMaxEndTimestamp } from "../utils/helpers.js"
import { useAuth } from "../contexts/AuthContext.jsx"

export default function Place() {
    const { isAdmin } = useAuth()
    const { placeId } = useParams()
    const api = useApi()
    const place = usePlace(placeId)
    const places = useRegularPlaces({ maxEnd: getMaxEndTimestamp(isAdmin()) })

    if (!place) {
        return null
    }

    return (
        <>
            <PageHeader
                name={place.name}
                categories={[place.getCategory("MOST_SPECIFIC_WITH_METADATA")]}
                onNameChanged={name => api.updatePlaceName(placeId, name)}
                onAddressChanged={address => api.getCoordinates(address).then(coordinates => api.updatePlaceLocation(placeId, coordinates.latitude, coordinates.longitude)).then(setPlace)} />
            <HighlightCarousel
                name={place.name}
                highlights={place.highlights}
                onHighlightRemoved={highlightId => api.removePlaceHighlight(placeId, highlightId)}
                onMainHighlightUpdated={highlightId => api.updatePlaceMainHighlight(placeId, highlightId)} />
            <CategoryBar categories={place.categories} />
            <LabelBar
                labels={place.labels}
                onLabelAdded={name => api.createPlaceLabel(placeId, name).then(fetchAndSetPlace)}
                onLabelRemoved={labelId => api.removePlaceLabel(placeId, labelId).then(fetchAndSetPlace)} />
            <PlaceContent
                place={place}
                onExcerptChanged={excerpt => api.updatePlaceExcerpt(placeId, excerpt).then(setPlace)}
                onExcerptRefreshed={() => api.updatePlaceExcerpt(placeId, null).then(setPlace)}
                onLocationChanged={(latitude, longitude) => api.updatePlaceLocation(placeId, latitude, longitude).then(setPlace)} />
            <DateTileGrid
                place={place}
                onAlbumRefreshed={albumId => api.refreshPlaceAlbum(placeId, albumId).then(fetchAndSetPlace)} />
            <TripBar trips={place.getPastTrips()} />
            {place.getAlbums().length > 0 && place.getPastTrips().length === 0
                && <hr className="w-full h-0.5 my-4 bg-gradient-to-r from-transparent via-gray-400 to-transparent" />}
            <NearbyPlaceTileGrid
                place={place}
                places={places}
                count={3} />
            <SunAltitudeBar place={place} />
        </>
    )
}