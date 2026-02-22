import { useParams } from "react-router-dom"
import { useMemo, useState } from "react"
import HighlightCarousel from "../components/HighlightCarousel"
import HighlightCandidateTileGrid from "../components/HighlightCandidateTileGrid"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { useCategory } from "../hooks/useCategory"
import { useAuth } from "../contexts/AuthContext.tsx"
import { UserRole } from "../types/CoreSwaggerTypes.ts"

export default function CategoryHighlightsPage() {
    const { categoryId } = useParams()
    const { hasRole } = useAuth()

    const { category, createCategoryHighlight } = useCategory(categoryId)
    const { places } = useRegularPlaces({ categoryId, include: ["highlights"], sort: "-score" })

    const [currentHighlights, setCurrentHighlights] = useState(null)

    const highlightCandidates = useMemo(() => places?.map(place => ({
        title: place.name,
        getPhotos: () => place.highlights?.filter(highlight => !category?.highlights?.some(h => h.photo.id === highlight.photo.id))?.map(highlight => highlight.photo)
    })).filter(group => group.getPhotos()?.length), [category, places])

    const handleHighlightCreated = async photoId => createCategoryHighlight(photoId)
        .then(_ => {
            setCurrentHighlights(currentHighlights.filter(h => h.id !== photoId))
        })

    const handleHighlightCandidateCreated = highlightCandidate => {
        if (!currentHighlights?.some(h => h.id === highlightCandidate.id)) {
            setCurrentHighlights([...(currentHighlights ?? []), highlightCandidate])
        }
    }

    return hasRole(UserRole.CategoryHighlightRead) && (!highlightCandidates || highlightCandidates.length > 0) && (
        <>
            {currentHighlights && (
                <HighlightCarousel
                    highlights={currentHighlights?.map((currentHighlightCandidate, index) => ({ id: index, photo: currentHighlightCandidate, url: { full: currentHighlightCandidate.url + "=w1200-h800", thumbnail: currentHighlightCandidate.url + "=w350-h233" } }))}
                    onHighlightCreated={hasRole(UserRole.CategoryHighlightEdit) && handleHighlightCreated}
                    onHighlightRemoved={hasRole(UserRole.CategoryHighlightEdit) && (async highlightId => setCurrentHighlights(currentHighlights.splice(highlightId, 1)))} />
            )}
            <HighlightCandidateTileGrid
                name={category?.name}
                categories={category && [category]}
                highlightCandidates={highlightCandidates}
                onHighlightCandidateCreated={hasRole(UserRole.CategoryHighlightEdit) && handleHighlightCandidateCreated} />
        </>
    )
}