import { useParams } from "react-router-dom"
import { useEffect, useMemo, useState } from "react"
import HighlightCarousel from "../components/HighlightCarousel"
import HighlightCandidateTileGrid from "../components/HighlightCandidateTileGrid"
import { useYear } from "../hooks/useYear"
import { useRegularTrips } from "../hooks/useRegularTrips"

export default function YearHighlightsPage() {
    const { year: yearParameter } = useParams()

    const { year, createYearHighlight } = useYear(yearParameter)
    const trips = useRegularTrips({ year: yearParameter, include: "highlights" })

    const [currentHighlights, setCurrentHighlights] = useState(null)

    const highlightCandidates = useMemo(() => year && trips && trips.map(trip => ({
        title: trip.getFullName(),
        highlightCandidates: trip.highlights?.filter(highlight => !year.highlights?.some(h => h.photo.id === highlight.photo.id))?.map(highlight => highlight.photo)
    })).filter(group => group.highlightCandidates?.length), [year, trips])

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