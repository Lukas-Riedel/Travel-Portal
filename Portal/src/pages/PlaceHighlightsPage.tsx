import { useParams } from "react-router-dom"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useMemo, useState } from "react"
import { usePlace } from "../hooks/usePlace"
import { listPlaceAlbumPhotos } from "../clients/coreClient"
import HighlightCarousel from "../components/HighlightCarousel.tsx"
import HighlightCandidateTileGrid from "../components/HighlightCandidateTileGrid"
import { UserRole, type Photo } from "../types/CoreSwaggerTypes.ts"
import { InternalCategoryCategory } from "../types/InternalCategoryCategory.ts"
import { useTranslation } from "react-i18next"
import { formatTimestamp, getCurrentTimestamp } from "../utils/timeUtils.ts"

export default function PlaceHighlightsPage() {
    const { placeId } = useParams()
    const { hasRole } = useAuth()
    const { t } = useTranslation()

    const { place, createPlaceHighlight } = usePlace(placeId)

    const [currentPhotos, setCurrentPhotos] = useState<Photo[] | null>(null)

    const highlightCandidates = useMemo(() => place?.dates
        .map(date => date.album)
        .filter(Boolean)
        .reverse()
        .map(album => ({
            title: album.name,
            getPhotos: () => listPlaceAlbumPhotos(placeId, album.id)
                .then(photos => photos
                    .filter(photo => !place.highlights
                        ?.some(highlight => highlight.photo.id === photo.id)))
        })), [place])

    const handleHighlightCreated = async (photoId: string) => createPlaceHighlight(photoId)
        .then(highlight => (setCurrentPhotos(previous => previous ? previous.filter(photo => photo.id !== photoId) : null), highlight))

    const handleHighlightCandidateCreated = async (highlightCandidate: Photo) => {
        setCurrentPhotos(previous => previous?.some(photo => photo.id === highlightCandidate.id) ? previous : [...(previous ?? []), highlightCandidate])
    }

    const handleHighlightRemoved = async (photoId: string) => {
        setCurrentPhotos(previous => previous.filter(photo => photo.id !== photoId))
    }

    return hasRole(UserRole.PlaceHighlightRead) && (
        <>
            {currentPhotos && (
                <HighlightCarousel
                    // TODO: Create a class with the method to obtain the full/thumbnail URL.
                    highlights={currentPhotos?.map(currentHighlightCandidate => ({ id: currentHighlightCandidate.id, photo: currentHighlightCandidate, url: { full: currentHighlightCandidate.url + "=w1200-h800", thumbnail: currentHighlightCandidate.url + "=w350-h233" }, attributes: {} }))}
                    onHighlightCreated={hasRole(UserRole.PlaceHighlightEdit) && handleHighlightCreated}
                    onHighlightRemoved={hasRole(UserRole.PlaceHighlightEdit) && handleHighlightRemoved} />
            )}
            <HighlightCandidateTileGrid
                name={place?.name}
                description={formatTimestamp(getCurrentTimestamp(), t("general.format.date.year.included"))}
                categories={place && [place.getCategory(InternalCategoryCategory.MostSpecificWithMetadata)]}
                highlightCandidatesGroups={highlightCandidates}
                onHighlightCreated={hasRole(UserRole.PlaceHighlightEdit) && handleHighlightCreated}
                onHighlightCandidateCreated={hasRole(UserRole.PlaceHighlightEdit) && handleHighlightCandidateCreated} />
        </>
    )
}
