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
import { useAuth } from "../contexts/AuthContext.jsx"
import { useEvents } from "../hooks/useEvents.js"
import { useMemo } from "react"
import { createPlaceAlbumPhoto } from "../clients/coreClient.js"
import NoteCardGrid from "../components/NoteCardGrid.jsx"
import { HighlightType } from "../types/CoreSwaggerTypes.ts"

const nearbyPlacesCount = 3

export default function PlacePage() {
    const { isAdmin } = useAuth()
    const { placeId } = useParams()
    const { publishPhotosUploadingTriggeredEvent, publishPhotoReplacingTriggeredEvent, publishHighlightsSelectingTriggeredEvent } = useEvents()

    const { place, updatePlaceName, updatePlaceAddress, removePlaceHighlight, updatePlaceAlbumReviewed,
        updatePlaceMainHighlight, createPlaceLabel, removePlaceLabel, updatePlaceExcerpt, updatePlaceNoteContent,
        refreshPlaceExcerpt, updatePlaceLocation, refreshPlaceAlbum, updatePlaceHighlightQualityAttributes,
        createPlaceNote, removePlaceNote } = usePlace(placeId, nearbyPlacesCount)

    const mostSpecificCategory = useMemo(() => place?.getCategory("mostSpecificWithMetadata"), [place])

    const handlePhotoCorrected = async (placeId, albumId, fileName, data, replacedPhotoId) => createPlaceAlbumPhoto(placeId, albumId, fileName, data, replacedPhotoId).then(() => refreshPlaceAlbum(albumId))

    return (
        <>
            <PageHeader
                name={place?.name}
                categories={mostSpecificCategory && [mostSpecificCategory]}
                internalAttributes={{ "Kvalita": place?.quality && `${Math.round(place.quality)}%`, "Skóre": place?.score, "Počet highlightů": place?.highlights?.length }}
                onHighlightsSelectingTriggered={place?.dates?.some(date => date.album) && (highlightsCount => publishHighlightsSelectingTriggeredEvent(HighlightType.Place, placeId, highlightsCount, true))}
                onNameChanged={updatePlaceName} />
            <HighlightCarousel
                place={place}
                highlights={place && (place.highlights ?? [])}
                onPhotoReplaced={publishPhotoReplacingTriggeredEvent}
                onPhotoCorrected={handlePhotoCorrected}
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
                onLocationChanged={updatePlaceLocation}
                onPlaceReviewed={updatePlaceAlbumReviewed} />
            <DateTileGrid
                place={place}
                onAlbumRefreshed={refreshPlaceAlbum} />
            <TripBar trips={isAdmin ? place?.getAllTrips() : place?.getPastTrips()} />
            {place?.getAlbums().length > 0 && place.getPastTrips().length === 0
                && <hr className="w-full h-0.5 my-4 bg-gradient-to-r from-transparent via-gray-400 to-transparent" />}
            <NearbyPlaceTileGrid place={place} />
            <SunAltitudeBar place={place} />
            {isAdmin && (
                <NoteCardGrid
                    notes={place && (place.notes ?? [])}
                    onNoteCreated={createPlaceNote}
                    onNoteContentUpdated={updatePlaceNoteContent}
                    onNoteRemoved={removePlaceNote} />
            )}
        </>
    )
}