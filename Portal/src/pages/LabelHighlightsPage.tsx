import { useMemo, useState } from "react"
import { useParams } from "react-router-dom"

import HighlightCandidateTileGrid from "../components/HighlightCandidateTileGrid.tsx"
import HighlightCarousel from "../components/HighlightCarousel.tsx"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useLabel } from "../hooks/useLabel.ts"
import { useRegularPlaces } from "../hooks/useRegularPlaces.ts"
import { type Photo, PlaceIncludedEntity, PlaceSortingStrategy, UserRole } from "../types/CoreSwaggerTypes.ts"

export default function LabelHighlightsPage() {
    const { labelId } = useParams()
    const { hasRole } = useAuth()

    const { label, createLabelHighlight } = useLabel(labelId)
    const { places } = useRegularPlaces({ labelId, include: [PlaceIncludedEntity.Highlights], sort: PlaceSortingStrategy.ValueScore })

    const [currentPhotos, setCurrentPhotos] = useState<Photo[] | null>(null)

    const highlightCandidates = useMemo(() => places?.map(place => {
        const photos = place.highlights?.filter(highlight => !label?.highlights?.some(h => h.photo.id === highlight.photo.id))?.map(highlight => highlight.photo) ?? []
        return {
            title: place.name,
            getPhotos: () => Promise.resolve(photos)
        }
    }).filter(group => group.getPhotos !== undefined), [places, label])

    const handleHighlightCreated = async (photoId: string) => createLabelHighlight(photoId)
        .then(highlight => (setCurrentPhotos(previous => previous ? previous.filter(photo => photo.id !== photoId) : null), highlight))

    const handleHighlightCandidateCreated = async (highlightCandidate: Photo) => {
        setCurrentPhotos(previous => previous?.some(photo => photo.id === highlightCandidate.id) ? previous : [...(previous ?? []), highlightCandidate])
    }

    const handleHighlightRemoved = async (photoId: string) => {
        setCurrentPhotos(previous => previous?.filter(photo => photo.id !== photoId) ?? null)
    }

    return hasRole(UserRole.LabelHighlightRead) && (!highlightCandidates || highlightCandidates.length > 0) && (
        <>
            {currentPhotos && (
                <HighlightCarousel
                    highlights={currentPhotos?.map(currentHighlightCandidate => ({ id: currentHighlightCandidate.id, photo: currentHighlightCandidate, url: { full: currentHighlightCandidate.url, thumbnail: currentHighlightCandidate.url }, attributes: {} }))}
                    onHighlightCreated={hasRole(UserRole.LabelHighlightEdit) ? handleHighlightCreated : undefined}
                    onHighlightRemoved={hasRole(UserRole.LabelHighlightEdit) ? handleHighlightRemoved : undefined} />
            )}
            <HighlightCandidateTileGrid
                name={label?.name ?? null}
                categories={undefined}
                highlightCandidatesGroups={highlightCandidates ?? []}
                onHighlightCreated={hasRole(UserRole.LabelHighlightEdit) ? handleHighlightCreated : undefined}
                onHighlightCandidateCreated={hasRole(UserRole.LabelHighlightEdit) ? handleHighlightCandidateCreated : undefined} />
        </>
    )
}
