import { useParams } from "react-router-dom"
import { usePlace } from "../hooks/usePlace"
import { useMemo, useState } from "react"
import { listPlaceAlbumPhotos } from "../clients/coreClient"
import HighlightCarousel from "../components/HighlightCarousel.tsx"
import HighlightCandidateTileGrid from "../components/HighlightCandidateTileGrid"
import { getDateString } from "../utils/helpers"
import { UserRole } from "../types/CoreSwaggerTypes.ts"
import { useAuth } from "../contexts/AuthContext.tsx"

export default function PlaceHighlightsPage() {
    const { placeId } = useParams()
    const { hasRole } = useAuth()

    const { place, createPlaceHighlight } = usePlace(placeId)

    const [currentHighlights, setCurrentHighlights] = useState(null)

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

    const handleHighlightCreated = async photoId => createPlaceHighlight(photoId)
        .then(_ => {
            setCurrentHighlights(currentHighlights.filter(h => h.id !== photoId))
        })

    const handleHighlightCandidateCreated = highlightCandidate => {
        if (!currentHighlights?.some(h => h.id === highlightCandidate.id)) {
            setCurrentHighlights([...(currentHighlights ?? []), highlightCandidate])
        }
    }

    return hasRole(UserRole.PlaceHighlightRead) && (
        <>
            {currentHighlights && (
                <HighlightCarousel
                    highlights={currentHighlights?.map((currentHighlightCandidate, index) => ({ id: index, photo: currentHighlightCandidate, url: { full: currentHighlightCandidate.url + "=w1200-h800", thumbnail: currentHighlightCandidate.url + "=w350-h233" } }))}
                    onHighlightCreated={hasRole(UserRole.PlaceHighlightEdit) && handleHighlightCreated}
                    onHighlightRemoved={hasRole(UserRole.PlaceHighlightEdit) && (async highlightId => setCurrentHighlights(currentHighlights.splice(highlightId, 1)))} />
            )}
            <HighlightCandidateTileGrid
                name={place?.name}
                description={getDateString(Date.now() / 1000)}
                categories={place && [place.getCategory("mostSpecificWithMetadata")]}
                highlightCandidatesGroups={highlightCandidates}
                onHighlightCandidateCreated={hasRole(UserRole.PlaceHighlightEdit) && handleHighlightCandidateCreated} />
        </>
    )
}