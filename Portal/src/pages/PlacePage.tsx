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

    const mostSpecificCategory = place?.getCategory(InternalCategoryCategory.MostSpecificWithMetadata)

    const attributes: Record<string, string | number | undefined> = {
        [t("place.attribute.quality")]: place?.quality && `${Math.round(place.quality)}%`,
        [t("place.attribute.tier")]: place ? getHighlightsTier(place.highlights ?? [], place.mainHighlight) : undefined,
        [t("place.attribute.score")]: place?.score,
        [t("place.attribute.highlightsCount")]: place?.highlights?.length,
        [t("place.attribute.elevation")]: place?.elevation && formatMeters(place.elevation)
    }

    const handlePhotoCorrected = async (placeId: string, albumId: string, fileName: string, base64Data: string, photoId: string) => createPlaceAlbumPhoto(placeId, albumId, fileName, base64Data, photoId)
        .then(({ batchId }) => refreshPlaceAlbum(albumId, undefined, batchId))
        .then(_ => listPlaceAlbumPhotos(placeId, albumId))
        .then(photos => photos.find(photo => photo.id === photoId)!)

    return hasRole(UserRole.PlaceRead) && (
        <>
            <PlaceReviewAlertBar
                place={place}
                onPlaceReviewed={hasRole(UserRole.PlaceAlbumEdit) && hasRole(UserRole.PortalWarningRead) ? updatePlaceAlbumsReviewed : undefined} />
            <PageHeader
                name={place?.name ?? null}
                categories={mostSpecificCategory ? [mostSpecificCategory] : undefined}
                internalAttributes={hasRole(UserRole.PlaceEdit) ? attributes : undefined}
                onHighlightsRefreshed={hasRole(UserRole.PlaceHighlightEdit) && place?.dates?.some(date => date.album) ? (highlightsCount => refreshPlaceHighlights(highlightsCount)) : undefined}
                onNameChanged={hasRole(UserRole.PlaceEdit) ? updatePlaceName : undefined} />
            <HighlightCarousel
                place={place ?? undefined}
                highlights={place ? (place.highlights ?? []).filter(highlight => (highlight.photo.timestamp ?? 0) < getCurrentOrMaximumAllowedTimestamp()) : null}
                onPhotoReplaced={hasRole(UserRole.PlaceAlbumEdit) ? publishPhotoReplacingTriggeredEvent : undefined}
                onPhotoCorrected={hasRole(UserRole.PlaceAlbumEdit) ? handlePhotoCorrected : undefined}
                onHighlightRemoved={hasRole(UserRole.PlaceHighlightEdit) ? removePlaceHighlight : undefined}
                onMainHighlightUpdated={hasRole(UserRole.PlaceEdit) ? updatePlaceMainHighlight : undefined}
                onHighlightQualityAttributesUpdated={hasRole(UserRole.HighlightEdit) ? updatePlaceHighlightQualityAttributes : undefined} />
            <CategoryBar categories={place ? (place.categories ?? []) : null} />
            <LabelBar
                labels={place ? (place.labels ?? []) : null}
                onLabelAdded={hasRole(UserRole.PlaceLabelEdit) ? createPlaceLabel : undefined}
                onLabelRemoved={hasRole(UserRole.PlaceLabelEdit) ? removePlaceLabel : undefined} />
            <PlaceContent
                place={place}
                onPhotosAdded={hasRole(UserRole.PlaceAlbumEdit) ? publishPhotosUploadingTriggeredEvent : undefined}
                onExcerptChanged={hasRole(UserRole.PlaceEdit) ? updatePlaceExcerpt : undefined}
                onExcerptRefreshed={hasRole(UserRole.PlaceEdit) ? refreshPlaceExcerpt : undefined}
                onAddressChanged={hasRole(UserRole.PlaceEdit) ? updatePlaceAddress : undefined}
                onLocationChanged={hasRole(UserRole.PlaceEdit) ? updatePlaceLocation : undefined} />
            <DateTileGrid
                place={place}
                onAlbumRefreshed={hasRole(UserRole.PlaceAlbumEdit) ? refreshPlaceAlbum : undefined} />
            <TripBar trips={hasRole(UserRole.PortalFutureRead) ? (place?.getAllTrips() ?? null) : (place?.getPastTrips() ?? null)} />
            {place != null && place.getAlbums().length > 0 && place.getPastTrips().length === 0
                && <hr className="w-full h-0.5 my-4 bg-gradient-to-r from-transparent via-gray-400 to-transparent" />}
            <NearbyPlaceTileGrid place={place} />
            <SunAltitudeBar place={place} />
            {hasRole(UserRole.PlaceNoteRead) && (
                <NoteCardGrid
                    rowSize={3}
                    notes={place ? (place.notes ?? []) : null}
                    onNoteCreated={createPlaceNote}
                    onNoteContentUpdated={updatePlaceNoteContent}
                    onNoteRemoved={removePlaceNote} />
            )}
        </>
    )
}
