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
import { useEvents } from "../hooks/useEvents.js"
import NoteBar from "../components/NoteBar.jsx"

export default function PlacePage() {
    const { isAdmin } = useAuth()
    const { placeId } = useParams()
    const { publishPhotosUploadingTriggeredEvent, publishPhotoReplacingTriggeredEvent } = useEvents()

    const { place, updatePlaceName, updatePlaceAddress, removePlaceHighlight,
        updatePlaceMainHighlight, createPlaceLabel, removePlaceLabel, updatePlaceExcerpt,
        refreshPlaceExcerpt, updatePlaceLocation, refreshPlaceAlbum, updatePlaceHighlightQualityAttributes,
        createPlaceNote, removePlaceNote } = usePlace(placeId)
    const places = useTimeFilteredRegularPlaces({ include: "categories", sort: "-score" })

    return (
        <>
            <PageHeader
                name={place?.name}
                categories={place && [place.getCategory("mostSpecificWithMetadata")]}
                internalAttributes={{ "Kvalita": place?.quality && `${Math.round(place.quality)}%`, "Skóre": place?.score }}
                showHighlightsButton={true}
                onNameChanged={updatePlaceName} />
            <HighlightCarousel
                place={place}
                highlights={place && (place.highlights ?? [])}
                onPhotoReplaced={publishPhotoReplacingTriggeredEvent}
                onHighlightRemoved={removePlaceHighlight}
                onMainHighlightUpdated={updatePlaceMainHighlight}
                onHighlightQualityAttributesUpdated={updatePlaceHighlightQualityAttributes} />
            <CategoryBar categories={place && (place.categories ?? [])} />
            <LabelBar
                labels={place && (place.labels ?? [])}
                onLabelAdded={createPlaceLabel}
                onLabelRemoved={removePlaceLabel} />
            <PlaceContent
                place={place}
                onPhotosAdded={publishPhotosUploadingTriggeredEvent}
                onExcerptChanged={updatePlaceExcerpt}
                onExcerptRefreshed={refreshPlaceExcerpt}
                onAddressChanged={updatePlaceAddress}
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
            {isAdmin && (
                <NoteBar
                    notes={place && (place.notes ?? [])}
                    onNoteCreated={createPlaceNote}
                    onNoteRemoved={removePlaceNote} />
            )}
        </>
    )
}