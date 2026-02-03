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
import { HighlightType, UserRole } from "../types/CoreSwaggerTypes.ts"
import { getCurrentOrMaximumAllowedTimestamp } from "../utils/timeUtils.ts"

const nearbyPlacesCount = 3

export default function PlacePage() {
    const { hasRole } = useAuth()
    const { placeId } = useParams()
    const { publishPhotosUploadingTriggeredEvent, publishPhotoReplacingTriggeredEvent, publishHighlightsSelectingTriggeredEvent } = useEvents()

    const { place, updatePlaceName, updatePlaceAddress, removePlaceHighlight, updatePlaceAlbumReviewed,
        updatePlaceMainHighlight, createPlaceLabel, removePlaceLabel, updatePlaceExcerpt, updatePlaceNoteContent,
        refreshPlaceExcerpt, updatePlaceLocation, refreshPlaceAlbum, updatePlaceHighlightQualityAttributes,
        createPlaceNote, removePlaceNote } = usePlace(placeId, nearbyPlacesCount)

    const mostSpecificCategory = useMemo(() => place?.getCategory("mostSpecificWithMetadata"), [place])

    const handlePhotoCorrected = async (placeId, albumId, fileName, data, replacedPhotoId) => createPlaceAlbumPhoto(placeId, albumId, fileName, data, replacedPhotoId).then(() => refreshPlaceAlbum(albumId))

    return hasRole(UserRole.PlaceRead) && (
        <>
            <PageHeader
                name={place?.name}
                categories={mostSpecificCategory && [mostSpecificCategory]}
                internalAttributes={hasRole(UserRole.PlaceEdit) && { "Kvalita": place?.quality && `${Math.round(place.quality)}%`, "Skóre": place?.score, "Počet highlightů": place?.highlights?.length }}
                onHighlightsSelectingTriggered={hasRole(UserRole.PlaceHighlightEdit) && place?.dates?.some(date => date.album) && (highlightsCount => publishHighlightsSelectingTriggeredEvent(HighlightType.Place, placeId, place.name, highlightsCount, true))}
                onNameChanged={hasRole(UserRole.PlaceEdit) && updatePlaceName} />
            <HighlightCarousel
                place={place}
                highlights={place && (place.highlights ?? []).filter(highlight => highlight.photo.timestamp < getCurrentOrMaximumAllowedTimestamp())}
                onPhotoReplaced={hasRole(UserRole.PlaceAlbumEdit) && publishPhotoReplacingTriggeredEvent}
                onPhotoCorrected={hasRole(UserRole.PlaceAlbumEdit) && handlePhotoCorrected}
                onHighlightRemoved={hasRole(UserRole.PlaceHighlightEdit) && removePlaceHighlight}
                onMainHighlightUpdated={hasRole(UserRole.PlaceEdit) && updatePlaceMainHighlight}
                onHighlightQualityAttributesUpdated={hasRole(UserRole.HighlightEdit) && updatePlaceHighlightQualityAttributes} />
            <CategoryBar categories={place && (place.categories ?? [])} />
            <LabelBar
                labels={place && (place.labels ?? [])}
                onLabelAdded={hasRole(UserRole.PlaceLabelEdit) && createPlaceLabel}
                onLabelRemoved={hasRole(UserRole.PlaceLabelEdit) && removePlaceLabel} />
            <PlaceContent
                place={place}
                onPhotosAdded={hasRole(UserRole.PlaceAlbumEdit) && publishPhotosUploadingTriggeredEvent}
                onExcerptChanged={hasRole(UserRole.PlaceEdit) && updatePlaceExcerpt}
                onExcerptRefreshed={hasRole(UserRole.PlaceEdit) && refreshPlaceExcerpt}
                onAddressChanged={hasRole(UserRole.PlaceEdit) && updatePlaceAddress}
                onLocationChanged={hasRole(UserRole.PlaceEdit) && updatePlaceLocation}
                onPlaceReviewed={hasRole(UserRole.PlaceAlbumEdit) && updatePlaceAlbumReviewed} />
            <DateTileGrid
                place={place}
                onAlbumRefreshed={hasRole(UserRole.PlaceAlbumEdit) && refreshPlaceAlbum} />
            <TripBar trips={hasRole(UserRole.UiFutureRead) ? place?.getAllTrips() : place?.getPastTrips()} />
            {place?.getAlbums().length > 0 && place.getPastTrips().length === 0
                && <hr className="w-full h-0.5 my-4 bg-gradient-to-r from-transparent via-gray-400 to-transparent" />}
            <NearbyPlaceTileGrid place={place} />
            <SunAltitudeBar place={place} />
            {hasRole(UserRole.PlaceNoteRead) && (
                <NoteCardGrid
                    notes={place && (place.notes ?? [])}
                    onNoteCreated={createPlaceNote}
                    onNoteContentUpdated={updatePlaceNoteContent}
                    onNoteRemoved={removePlaceNote} />
            )}
        </>
    )
}