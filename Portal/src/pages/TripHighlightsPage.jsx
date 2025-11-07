import { useParams } from "react-router-dom"
import { useTrip } from "../hooks/useTrip"
import { useMemo, useState } from "react"
import { listPlaceAlbumPhotos } from "../clients/coreClient"
import HighlightCarousel from "../components/HighlightCarousel"
import HighlightCandidateTileGrid from "../components/HighlightCandidateTileGrid"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { getDateRangeString } from "../utils/helpers"

export default function TripHighlightsPage() {
    const { tripId } = useParams()

    const { trip, createTripHighlight } = useTrip(tripId)
    const { places } = useRegularPlaces({ tripId, include: "categories,dates", sort: "-oldest" })

    const [currentHighlights, setCurrentHighlights] = useState(null)

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
            }))
        ), [places, trip])

    const handleHighlightCreated = async photoId => createTripHighlight(photoId)
        .then(_ => {
            setCurrentHighlights(currentHighlights.filter(h => h.id !== photoId))
        })

    const handleHighlightCandidateCreated = highlightCandidate => {
        if (!currentHighlights?.some(h => h.id === highlightCandidate.id)) {
            setCurrentHighlights([...(currentHighlights ?? []), highlightCandidate])
        }
    }

    return (
        <>
            {currentHighlights && (
                <HighlightCarousel
                    highlights={currentHighlights?.map((currentHighlightCandidate, index) => ({ id: index, photo: currentHighlightCandidate, url: { full: currentHighlightCandidate.url + "=w1200-h800", thumbnail: currentHighlightCandidate.url + "=w350-h233" } }))}
                    onHighlightCreated={handleHighlightCreated}
                    onHighlightRemoved={async highlightId => setCurrentHighlights(currentHighlights.splice(highlightId, 1))} />
            )}
            <HighlightCandidateTileGrid
                name={trip?.getFullName()}
                description={getDateRangeString(trip?.start, trip?.end)}
                categories={places?.map(p => p.getCategory("mostSpecificWithMetadata")).filter(Boolean).filter((c, i, arr) => !arr.slice(0, i).some(x => x.id === c.id))}
                highlightCandidates={highlightCandidates}
                onHighlightCandidateCreated={handleHighlightCandidateCreated} />
        </>
    )
}