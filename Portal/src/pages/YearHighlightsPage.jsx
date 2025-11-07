import { useParams } from "react-router-dom"
import { useEffect, useMemo, useState } from "react"
import HighlightCarousel from "../components/HighlightCarousel"
import HighlightCandidateTileGrid from "../components/HighlightCandidateTileGrid"
import { useYear } from "../hooks/useYear"
import { useRegularTrips } from "../hooks/useRegularTrips"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { listPlaceAlbumPhotos } from "../clients/coreClient"

export default function YearHighlightsPage() {
    const { year: yearParameter } = useParams()

    const { year, createYearHighlight } = useYear(yearParameter)
    const trips = useRegularTrips({ year: yearParameter, include: "highlights" })
    const { places } = useRegularPlaces({ year: yearParameter, include: "dates" })

    const [highlightCandidates, setHighlightCandidates] = useState(null)
    const [currentHighlights, setCurrentHighlights] = useState(null)

    useEffect(() => {
        if (!trips || !year || !places) {
            return
        }

        const fetchPhotos = async () => {
            const dayTripHighlightCandidates = await Promise.all(places
                .flatMap(place => place.dates
                    .filter(date => !date.trip)
                    .map(date => date.album)
                    .filter(Boolean)
                    .reverse()
                    .map(album => listPlaceAlbumPhotos(place.id, album.id)
                        .then(photos => photos
                            .filter(photo => !year.highlights
                                ?.some(highlight => highlight.photo.id === photo.id)))
                        .then(photos => ({ title: album.name, highlightCandidates: photos })))))
            const tripHighlightCandidateGroups = trips.map(trip => ({
                title: trip.getFullName(),
                highlightCandidates: trip.highlights?.filter(highlight => !year.highlights?.some(h => h.photo.id === highlight.photo.id))?.map(highlight => highlight.photo)
            }))

            setHighlightCandidates([...tripHighlightCandidateGroups, ...dayTripHighlightCandidates].filter(group => group.highlightCandidates?.length))
        }

        fetchPhotos()
    }, [trips, year, places])

    useEffect(() => {
        if (highlightCandidates?.length && !currentHighlights) {
            setCurrentHighlights([highlightCandidates[0]?.highlightCandidates?.[0]])
        }
    }, [highlightCandidates?.length])

    const handleHighlightCreated = async photoId => createYearHighlight(photoId)
        .then(_ => {
            setCurrentHighlights(currentHighlights.filter(h => h.id !== photoId))
        })

    const handleHighlightCandidateCreated = highlightCandidate => {
        if (!currentHighlights.some(h => h.id === highlightCandidate.id)) {
            setCurrentHighlights([...currentHighlights, highlightCandidate])
        }
    }

    return (!highlightCandidates || highlightCandidates.length > 0) && (
        <>
            <HighlightCarousel
                highlights={currentHighlights?.map((currentHighlightCandidate, index) => ({ id: index, photo: currentHighlightCandidate, url: { full: currentHighlightCandidate.url, thumbnail: currentHighlightCandidate.url } }))}
                onHighlightCreated={handleHighlightCreated}
                onHighlightRemoved={async highlightId => setCurrentHighlights(currentHighlights.splice(highlightId, 1))} />
            <HighlightCandidateTileGrid
                highlightCandidates={highlightCandidates}
                onHighlightCandidateCreated={handleHighlightCandidateCreated} />
        </>
    )
}