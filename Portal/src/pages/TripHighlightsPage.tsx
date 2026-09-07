import { useParams } from "react-router-dom"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useMemo, useState } from "react"
import { useTrip } from "../hooks/useTrip"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { listPlaceAlbumPhotos } from "../clients/coreClient"
import HighlightCarousel from "../components/HighlightCarousel.tsx"
import HighlightCandidateTileGrid from "../components/HighlightCandidateTileGrid"
import { getDateRangeString } from "../utils/helpers"
import { PlaceIncludedEntity, PlaceSortingStrategy, UserRole, type Photo } from "../types/CoreSwaggerTypes.ts"
import { InternalCategoryCategory } from "../types/InternalCategoryCategory.ts"
import { formatDateRange } from "../utils/timeUtils.ts"
import { useTranslation } from "react-i18next"

export default function TripHighlightsPage() {
    const { tripId } = useParams()
    const { hasRole } = useAuth()
    const { t } = useTranslation()

    const { trip, createTripHighlight } = useTrip(tripId)
    const { places } = useRegularPlaces({ tripId, include: [PlaceIncludedEntity.Categories, PlaceIncludedEntity.Dates, PlaceIncludedEntity.Highlights], sort: PlaceSortingStrategy.ValueOldest })

    const [currentPhotos, setCurrentPhotos] = useState<Photo[] | null>(null)

    const highlightCandidates = useMemo(() => places
        ?.flatMap(place => place.dates
            .reverse()
            .map(date => date.album)
            .filter(Boolean)
            .reverse()
            .map(album => ({
                title: album.name,
                getPhotos: () => listPlaceAlbumPhotos(place.id, album.id)
                    .then(photos => photos
                        .filter(photo => !trip.highlights
                            ?.some(highlight => highlight.photo.id === photo.id)))
                    .then(photos => {
                        const highlightIds = new Set(places?.flatMap(place => place.highlights ?? []).map(h => h.photo.id))

                        return photos.slice().sort((a, b) => {
                            const aIsHighlighted = highlightIds.has(a.id)
                            const bIsHighlighted = highlightIds.has(b.id)

                            if (aIsHighlighted && !bIsHighlighted) {
                                return -1
                            }
                            if (!aIsHighlighted && bIsHighlighted) {
                                return 1
                            }

                            return 0
                        })
                    })
            }))
        ), [places, trip])

    const handleHighlightCreated = async (photoId: string) => createTripHighlight(photoId)
        .then(highlight => (setCurrentPhotos(previous => previous ? previous.filter(photo => photo.id !== photoId) : null), highlight))

    const handleHighlightCandidateCreated = async (highlightCandidate: Photo) => {
        setCurrentPhotos(previous => previous?.some(photo => photo.id === highlightCandidate.id) ? previous : [...(previous ?? []), highlightCandidate])
    }

    const handleHighlightRemoved = async (photoId: string) => {
        setCurrentPhotos(previous => previous.filter(photo => photo.id !== photoId))
    }

    return hasRole(UserRole.TripHighlightRead) && (
        <>
            {currentPhotos && (
                <HighlightCarousel
                    // TODO: Create a class with the method to obtain the full/thumbnail URL.
                    highlights={currentPhotos?.map(currentHighlightCandidate => ({ id: currentHighlightCandidate.id, photo: currentHighlightCandidate, url: { full: currentHighlightCandidate.url + "=w1200-h800", thumbnail: currentHighlightCandidate.url + "=w350-h233" }, attributes: {} }))}
                    onHighlightCreated={hasRole(UserRole.TripHighlightEdit) && handleHighlightCreated}
                    onHighlightRemoved={hasRole(UserRole.TripHighlightEdit) && handleHighlightRemoved} />
            )}
            <HighlightCandidateTileGrid
                name={trip?.getFullName()}
                description={formatDateRange(trip?.start, trip?.end, t("general.format.date.year.included"))}
                categories={places?.map(place => place.getCategory(InternalCategoryCategory.MostSpecificWithMetadata)).filter(Boolean).filter((c, i, arr) => !arr.slice(0, i).some(x => x.id === c.id))}
                highlightCandidatesGroups={highlightCandidates}
                onHighlightCreated={hasRole(UserRole.TripHighlightEdit) && handleHighlightCreated}
                onHighlightCandidateCreated={hasRole(UserRole.TripHighlightEdit) && handleHighlightCandidateCreated} />
        </>
    )
}
