import { useState } from "react"
import { useTranslation } from "react-i18next"
import { useParams } from "react-router-dom"

import { listPlaceAlbumPhotos } from "../clients/coreClient"
import HighlightCandidateTileGrid from "../components/HighlightCandidateTileGrid"
import HighlightCarousel from "../components/HighlightCarousel.tsx"
import { useAuth } from "../contexts/AuthContext.tsx"
import { usePlace } from "../hooks/usePlace"
import { type Photo,UserRole } from "../types/CoreSwaggerTypes.ts"
import { InternalCategoryCategory } from "../types/InternalCategoryCategory.ts"
import { getPlaceCategory } from "../utils/placeUtils.ts"
import { formatTimestamp, getCurrentTimestamp } from "../utils/timeUtils.ts"

export default function PlaceHighlightsPage() {
    const { placeId } = useParams()
    const { hasRole } = useAuth()
    const { t } = useTranslation()

    const { place, createPlaceHighlight } = usePlace(placeId)

    const [currentPhotos, setCurrentPhotos] = useState<Photo[] | null>(null)

    const highlightCandidates = (place?.dates ?? [])
        .map(date => date.album)
        .filter((a): a is NonNullable<typeof a> => a != null)
        .reverse()
        .map(album => ({
            title: album.name,
            getPhotos: () => listPlaceAlbumPhotos(placeId!, album.id)
                .then(photos => photos
                    .filter(photo => !place?.highlights
                        ?.some(highlight => highlight.photo.id === photo.id)))
        }))

    const handleHighlightCreated = async (photoId: string) => createPlaceHighlight(photoId)
        .then(highlight => (setCurrentPhotos(previous => previous ? previous.filter(photo => photo.id !== photoId) : null), highlight))

    const handleHighlightCandidateCreated = async (highlightCandidate: Photo) => {
        setCurrentPhotos(previous => previous?.some(photo => photo.id === highlightCandidate.id) ? previous : [...(previous ?? []), highlightCandidate])
    }

    const handleHighlightRemoved = async (photoId: string) => {
        setCurrentPhotos(previous => previous?.filter(photo => photo.id !== photoId) ?? null)
    }

    return hasRole(UserRole.PlaceHighlightRead) && (
        <>
            {currentPhotos && (
                <HighlightCarousel
                    // TODO: Create a class with the method to obtain the full/thumbnail URL.
                    highlights={currentPhotos?.map(currentHighlightCandidate => ({ id: currentHighlightCandidate.id, photo: currentHighlightCandidate, url: { full: currentHighlightCandidate.url + "=w1200-h800", thumbnail: currentHighlightCandidate.url + "=w350-h233" }, attributes: {} }))}
                    onHighlightCreated={hasRole(UserRole.PlaceHighlightEdit) ? handleHighlightCreated : undefined}
                    onHighlightRemoved={hasRole(UserRole.PlaceHighlightEdit) ? handleHighlightRemoved : undefined} />
            )}
            <HighlightCandidateTileGrid
                name={place?.name ?? null}
                description={formatTimestamp(getCurrentTimestamp(), t("general.format.date.year.included"))}
                categories={place ? [getPlaceCategory(place, InternalCategoryCategory.MostSpecificWithMetadata)].filter((c): c is NonNullable<typeof c> => c != null) : undefined}
                highlightCandidatesGroups={highlightCandidates ?? []}
                onHighlightCreated={hasRole(UserRole.PlaceHighlightEdit) ? handleHighlightCreated : undefined}
                onHighlightCandidateCreated={hasRole(UserRole.PlaceHighlightEdit) ? handleHighlightCandidateCreated : undefined} />
        </>
    )
}
