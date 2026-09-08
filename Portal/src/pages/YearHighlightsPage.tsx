import { useMemo, useState } from "react"
import { useParams } from "react-router-dom"

import { listPlaceAlbumPhotos } from "../clients/coreClient"
import HighlightCandidateTileGrid from "../components/HighlightCandidateTileGrid"
import HighlightCarousel from "../components/HighlightCarousel.tsx"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { useRegularTrips } from "../hooks/useRegularTrips"
import { useYear } from "../hooks/useYear"
import { type Photo,PlaceIncludedEntity, PlaceSortingStrategy, TripIncludedEntity, UserRole } from "../types/CoreSwaggerTypes.ts"

export default function YearHighlightsPage() {
    const { year: yearParameter } = useParams()
    const { hasRole } = useAuth()

    const { year, createYearHighlight } = useYear(Number(yearParameter))
    const { trips } = useRegularTrips({ year: Number(yearParameter), include: [TripIncludedEntity.Highlights] })
    const { places } = useRegularPlaces({ year: Number(yearParameter), include: [PlaceIncludedEntity.Dates], sort: PlaceSortingStrategy.ValueOldest })

    const [currentPhotos, setCurrentPhotos] = useState<Photo[] | null>(null)

    const highlightCandidates = useMemo(() => {
        const tripHighlightCandidates = trips?.map(trip => {
            const photos = trip.highlights?.filter(highlight => !year?.highlights?.some(h => h.photo.id === highlight.photo.id))?.map(highlight => highlight.photo) ?? []
            return {
                title: trip.getFullName(),
                getPhotos: () => Promise.resolve(photos)
            }
        }).filter(group => group.getPhotos !== undefined)

        const dayTripHighlightCandidates = places
            ?.flatMap(place => (place.dates ?? [])
                .filter(date => !date.trip)
                .reverse()
                .map(date => date.album)
                .filter((a): a is NonNullable<typeof a> => a != null)
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
        setCurrentPhotos(previous => previous?.filter(photo => photo.id !== photoId) ?? null)
    }

    return hasRole(UserRole.YearHighlightRead) && (!highlightCandidates || highlightCandidates.length > 0) && (
        <>
            {currentPhotos && (
                <HighlightCarousel
                    highlights={currentPhotos?.map(currentHighlightCandidate => ({ id: currentHighlightCandidate.id, photo: currentHighlightCandidate, url: { full: currentHighlightCandidate.url, thumbnail: currentHighlightCandidate.url }, attributes: {} }))}
                    onHighlightCreated={hasRole(UserRole.YearHighlightEdit) ? handleHighlightCreated : undefined}
                    onHighlightRemoved={hasRole(UserRole.YearHighlightEdit) ? handleHighlightRemoved : undefined} />
            )}
            <HighlightCandidateTileGrid
                name={yearParameter ?? null}
                highlightCandidatesGroups={highlightCandidates ?? []}
                onHighlightCreated={hasRole(UserRole.YearHighlightEdit) ? handleHighlightCreated : undefined}
                onHighlightCandidateCreated={hasRole(UserRole.YearHighlightEdit) ? handleHighlightCandidateCreated : undefined} />
        </>
    )
}
