import { useMemo } from "react"
import { useTranslation } from "react-i18next"
import { useParams } from "react-router-dom"

import { createPlaceAlbumPhoto, listPlaceAlbumPhotos } from "../clients/coreClient.js"
import CategoryBar from "../components/CategoryBar.jsx"
import DateTileGrid from "../components/DateTileGrid.jsx"
import HighlightCarousel from "../components/HighlightCarousel.tsx"
import LabelBar from "../components/LabelBar.jsx"
import NearbyPlaceTileGrid from "../components/NearbyPlaceTileGrid.jsx"
import NoteCardGrid from "../components/NoteCardGrid.jsx"
import PageHeader from "../components/PageHeader.jsx"
import PlaceContent from "../components/PlaceContent.jsx"
import PlaceReviewAlertBar from "../components/PlaceReviewAlertBar.tsx"
import SunAltitudeBar from "../components/SunAltitudeBar.jsx"
import TripBar from "../components/TripBar.jsx"
import { useAuth } from "../contexts/AuthContext.jsx"
import { useEvents } from "../hooks/useEvents.js"
import { useFormatters } from "../hooks/useFormatters.ts"
import { usePlace } from "../hooks/usePlace.js"
import { UserRole } from "../types/CoreSwaggerTypes.ts"
import { InternalCategoryCategory } from "../types/InternalCategoryCategory.ts"
import { getHighlightsTier } from "../utils/highlightUtils.ts"
import { getCurrentOrMaximumAllowedTimestamp } from "../utils/timeUtils.ts"

const NEARBY_PLACES_COUNT = 3

export default function PlacePage() {
    const { placeId } = useParams()
    const { hasRole } = useAuth()
    const { publishPhotosUploadingTriggeredEvent, publishPhotoReplacingTriggeredEvent } = useEvents()
    const { t } = useTranslation()
    const { formatMeters } = useFormatters()

    const { place, updatePlaceName, updatePlaceAddress, removePlaceHighlight, updatePlaceAlbumsReviewed,
        updatePlaceMainHighlight, createPlaceLabel, removePlaceLabel, updatePlaceExcerpt, updatePlaceNoteContent,
        refreshPlaceExcerpt, updatePlaceLocation, refreshPlaceAlbum, updatePlaceHighlightQualityAttributes,
        createPlaceNote, removePlaceNote, refreshPlaceHighlights } = usePlace(placeId, NEARBY_PLACES_COUNT)

    const mostSpecificCategory = useMemo(() => place?.getCategory(InternalCategoryCategory.MostSpecificWithMetadata), [place])

    const attributes = {
        [t("place.attribute.quality")]: place?.quality && `${Math.round(place.quality)}%`,
        [t("place.attribute.tier")]: place && getHighlightsTier(place?.highlights ?? [], place?.mainHighlight),
        [t("place.attribute.score")]: place?.score,
        [t("place.attribute.highlightsCount")]: place?.highlights?.length,
        [t("place.attribute.elevation")]: place?.elevation && formatMeters(place.elevation)
    }

    const handlePhotoCorrected = async (placeId: string, albumId: string, fileName: string, base64Data: string, photoId: string) => createPlaceAlbumPhoto(placeId, albumId, fileName, base64Data, photoId)
        .then(({ batchId }) => refreshPlaceAlbum(albumId, undefined, batchId))
        .then(_ => listPlaceAlbumPhotos(placeId, albumId))
        .then(photos => photos.find(photo => photo.id === photoId))

    return hasRole(UserRole.PlaceRead) && (
        <>
            <PlaceReviewAlertBar
                place={place}
                onPlaceReviewed={hasRole(UserRole.PlaceAlbumEdit) && hasRole(UserRole.PortalWarningRead) && updatePlaceAlbumsReviewed} />
            <PageHeader
                name={place?.name ?? null}
                categories={mostSpecificCategory && [mostSpecificCategory]}
                internalAttributes={hasRole(UserRole.PlaceEdit) && attributes}
                onHighlightsRefreshed={hasRole(UserRole.PlaceHighlightEdit) && place?.dates?.some(date => date.album) && (highlightsCount => refreshPlaceHighlights(highlightsCount))}
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
                onLocationChanged={hasRole(UserRole.PlaceEdit) && updatePlaceLocation} />
            <DateTileGrid
                place={place}
                onAlbumRefreshed={hasRole(UserRole.PlaceAlbumEdit) && refreshPlaceAlbum} />
            <TripBar trips={hasRole(UserRole.PortalFutureRead) ? place?.getAllTrips() : place?.getPastTrips()} />
            {place?.getAlbums().length > 0 && place.getPastTrips().length === 0
                && <hr className="w-full h-0.5 my-4 bg-gradient-to-r from-transparent via-gray-400 to-transparent" />}
            <NearbyPlaceTileGrid place={place} />
            <SunAltitudeBar place={place} />
            {hasRole(UserRole.PlaceNoteRead) && (
                <NoteCardGrid
                    rowSize={3}
                    notes={place && (place.notes ?? [])}
                    onNoteCreated={createPlaceNote}
                    onNoteContentUpdated={updatePlaceNoteContent}
                    onNoteRemoved={removePlaceNote} />
            )}
        </>
    )
}
