import { useParams } from "react-router-dom"
import { useEffect, useMemo, useState } from "react"
import HighlightCarousel from "../components/HighlightCarousel"
import HighlightCandidateTileGrid from "../components/HighlightCandidateTileGrid"
import { useYear } from "../hooks/useYear"
import { useRegularTrips } from "../hooks/useRegularTrips"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { listPlaceAlbumPhotos } from "../clients/coreClient"
import { UserRole } from "../types/CoreSwaggerTypes.ts"
import { useAuth } from "../contexts/AuthContext.tsx"

export default function YearHighlightsPage() {
    const { year: yearParameter } = useParams()
    const { hasRole } = useAuth()

    const { year, createYearHighlight } = useYear(yearParameter)
    const { trips } = useRegularTrips({ year: yearParameter, include: ["highlights"] })
    const { places } = useRegularPlaces({ year: yearParameter, include: ["dates"], sort: "-oldest" })

    const [currentHighlights, setCurrentHighlights] = useState(null)

    const highlightCandidates = useMemo(() => {
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
                            .filter(photo => !year.highlights
                                ?.some(highlight => highlight.photo.id === photo.id)))
                })))

        const tripHighlightCandidates = trips?.map(trip => ({
            title: trip.getFullName(),
            getPhotos: () => trip.highlights?.filter(highlight => !year.highlights?.some(h => h.photo.id === highlight.photo.id))?.map(highlight => highlight.photo)
        }))?.filter(group => group.getPhotos()?.length)

        return [...(tripHighlightCandidates ?? []), ...(dayTripHighlightCandidates ?? [])]
    }, [places, year, trips])

    const handleHighlightCreated = async photoId => createYearHighlight(photoId)
        .then(_ => {
            setCurrentHighlights(currentHighlights.filter(h => h.id !== photoId))
        })

    const handleHighlightCandidateCreated = highlightCandidate => {
        if (!currentHighlights?.some(h => h.id === highlightCandidate.id)) {
            setCurrentHighlights([...(currentHighlights ?? []), highlightCandidate])
        }
    }

    return hasRole(UserRole.YearHighlightRead) && (!highlightCandidates || highlightCandidates.length > 0) && (
        <>
            {currentHighlights && (
                <HighlightCarousel
                    highlights={currentHighlights?.map((currentHighlightCandidate, index) => ({ id: index, photo: currentHighlightCandidate, url: { full: currentHighlightCandidate.url + "=w1200-h800", thumbnail: currentHighlightCandidate.url + "=w350-h233" } }))}
                    onHighlightCreated={hasRole(UserRole.YearHighlightEdit) && handleHighlightCreated}
                    onHighlightRemoved={hasRole(UserRole.YearHighlightEdit) && (async highlightId => setCurrentHighlights(currentHighlights.splice(highlightId, 1)))} />
            )}
            <HighlightCandidateTileGrid
                highlightCandidates={highlightCandidates}
                onHighlightCandidateCreated={hasRole(UserRole.YearHighlightEdit) && handleHighlightCandidateCreated} />
        </>
    )
}