import { useParams } from "react-router-dom"
import { useMemo, useState } from "react"
import HighlightCarousel from "../components/HighlightCarousel.tsx"
import HighlightCandidateTileGrid from "../components/HighlightCandidateTileGrid.tsx"
import { useRegularPlaces } from "../hooks/useRegularPlaces.ts"
import { useCategory } from "../hooks/useCategory.ts"
import { useAuth } from "../contexts/AuthContext.tsx"
import { PlaceIncludedEntity, PlaceSortingStrategy, UserRole, type Photo } from "../types/CoreSwaggerTypes.ts"

export default function CategoryHighlightsPage() {
    const { categoryId } = useParams()
    const { hasRole } = useAuth()

    const { category, createCategoryHighlight } = useCategory(categoryId)
    const { places } = useRegularPlaces({ categoryId, include: [PlaceIncludedEntity.Highlights], sort: PlaceSortingStrategy.ValueScore })

    const [currentPhotos, setCurrentPhotos] = useState<Photo[] | null>(null)

    const highlightCandidates = useMemo(() => places?.map(place => {
        const photos = place.highlights?.filter(highlight => !category?.highlights?.some(h => h.photo.id === highlight.photo.id))?.map(highlight => highlight.photo)
        return {
            title: place.name,
            photos,
            getPhotos: () => Promise.resolve(photos)
        }
    }).filter(group => group.photos?.length), [category, places])

    const handleHighlightCreated = async (photoId: string) => createCategoryHighlight(photoId)
        .then(highlight => (setCurrentPhotos(previous => previous ? previous.filter(photo => photo.id !== photoId) : null), highlight))

    const handleHighlightCandidateCreated = async (highlightCandidate: Photo) => {
        setCurrentPhotos(previous => previous?.some(photo => photo.id === highlightCandidate.id) ? previous : [...(previous ?? []), highlightCandidate])
    }

    const handleHighlightRemoved = async (photoId: string) => {
        setCurrentPhotos(previous => previous.filter(photo => photo.id !== photoId))
    }

    return hasRole(UserRole.CategoryHighlightRead) && (!highlightCandidates || highlightCandidates.length > 0) && (
        <>
            {currentPhotos && (
                <HighlightCarousel
                    highlights={currentPhotos?.map(currentHighlightCandidate => ({ id: currentHighlightCandidate.id, photo: currentHighlightCandidate, url: { full: currentHighlightCandidate.url, thumbnail: currentHighlightCandidate.url }, attributes: {} }))}
                    onHighlightCreated={hasRole(UserRole.CategoryHighlightEdit) && handleHighlightCreated}
                    onHighlightRemoved={hasRole(UserRole.CategoryHighlightEdit) && handleHighlightRemoved} />
            )}
            <HighlightCandidateTileGrid
                name={category?.name}
                categories={category && [category]}
                highlightCandidatesGroups={highlightCandidates}
                onHighlightCreated={hasRole(UserRole.CategoryHighlightEdit) && handleHighlightCreated}
                onHighlightCandidateCreated={hasRole(UserRole.CategoryHighlightEdit) && handleHighlightCandidateCreated} />
        </>
    )
}