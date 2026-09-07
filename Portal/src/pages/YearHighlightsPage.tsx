import { useParams } from "react-router-dom"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useMemo, useState } from "react"
import { useYear } from "../hooks/useYear"
import { useRegularTrips } from "../hooks/useRegularTrips"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { listPlaceAlbumPhotos } from "../clients/coreClient"
import HighlightCarousel from "../components/HighlightCarousel.tsx"
import HighlightCandidateTileGrid from "../components/HighlightCandidateTileGrid"
import { PlaceIncludedEntity, PlaceSortingStrategy, TripIncludedEntity, UserRole, type Photo } from "../types/CoreSwaggerTypes.ts"

export default function YearHighlightsPage() {
    const { year: yearParameter } = useParams()
    const { hasRole } = useAuth()

    const { year, createYearHighlight } = useYear(Number(yearParameter))
    const { trips } = useRegularTrips({ year: Number(yearParameter), include: [TripIncludedEntity.Highlights] })
    const { places } = useRegularPlaces({ year: Number(yearParameter), include: [PlaceIncludedEntity.Dates], sort: PlaceSortingStrategy.ValueOldest })

    const [currentPhotos, setCurrentPhotos] = useState<Photo[] | null>(null)

    const highlightCandidates = useMemo(() => {
        const tripHighlightCandidates = trips?.map(trip => {
            const photos = trip.highlights?.filter(highlight => !year?.highlights?.some(h => h.photo.id === highlight.photo.id))?.map(highlight => highlight.photo)
            return {
                title: trip.getFullName(),
                photos,
                getPhotos: () => Promise.resolve(photos)
            }
        }).filter(group => group.photos?.length)

        const dayTripHighlightCandidates = places
            ?.flatMap(place => place.dates
                .filter(date => !date.trip)
                .reverse()
                .map(date => date.album)
                .filter(Boolean)
                .reverse()
                .map(album => ({
                    title: album.name,
                    getPhotos: () => listPlaceAlbumPhotos(place.id, album.id)
                        .then(photos => photos
                            .filter(photo => !year?.highlights
                                ?.some(highlight => highlight.photo.id === photo.id)))
                })))

        return [...(tripHighlightCandidates ?? []), ...(dayTripHighlightCandidates ?? [])]
    }, [places, year, trips])

    const handleHighlightCreated = async (photoId: string) => createYearHighlight(photoId)
        .then(highlight => (setCurrentPhotos(previous => previous ? previous.filter(photo => photo.id !== photoId) : null), highlight))

    const handleHighlightCandidateCreated = async (highlightCandidate: Photo) => {
        setCurrentPhotos(previous => previous?.some(photo => photo.id === highlightCandidate.id) ? previous : [...(previous ?? []), highlightCandidate])
    }

    const handleHighlightRemoved = async (photoId: string) => {
        setCurrentPhotos(previous => previous.filter(photo => photo.id !== photoId))
    }

    return hasRole(UserRole.YearHighlightRead) && (!highlightCandidates || highlightCandidates.length > 0) && (
        <>
            {currentPhotos && (
                <HighlightCarousel
                    highlights={currentPhotos?.map(currentHighlightCandidate => ({ id: currentHighlightCandidate.id, photo: currentHighlightCandidate, url: { full: currentHighlightCandidate.url, thumbnail: currentHighlightCandidate.url }, attributes: {} }))}
                    onHighlightCreated={hasRole(UserRole.YearHighlightEdit) && handleHighlightCreated}
                    onHighlightRemoved={hasRole(UserRole.YearHighlightEdit) && handleHighlightRemoved} />
            )}
            <HighlightCandidateTileGrid
                name={yearParameter}
                highlightCandidatesGroups={highlightCandidates}
                onHighlightCreated={hasRole(UserRole.YearHighlightEdit) && handleHighlightCreated}
                onHighlightCandidateCreated={hasRole(UserRole.YearHighlightEdit) && handleHighlightCandidateCreated} />
        </>
    )
}
