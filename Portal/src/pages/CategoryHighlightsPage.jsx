import { useParams } from "react-router-dom"
import { useEffect, useMemo, useState } from "react"
import HighlightCarousel from "../components/HighlightCarousel"
import HighlightCandidateTileGrid from "../components/HighlightCandidateTileGrid"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { useCategory } from "../hooks/useCategory"

export default function CategoryHighlightsPage() {
    const { categoryId } = useParams()

    const { category, createCategoryHighlight } = useCategory(categoryId)
    const places = useRegularPlaces({ categoryId, include: "highlights", sort: "-score" })

    const [currentHighlights, setCurrentHighlights] = useState(null)

    const highlightCandidates = useMemo(() => category && places && places.map(place => ({
        title: place.name,
        highlightCandidates: place.highlights?.filter(highlight => !category.highlights?.some(h => h.photo.id === highlight.photo.id))?.map(highlight => highlight.photo)
    })).filter(group => group.highlightCandidates?.length), [category, places])

    useEffect(() => {
        if (highlightCandidates?.length && !currentHighlights) {
            setCurrentHighlights([highlightCandidates[0]?.highlightCandidates?.[0]])
        }
    }, [highlightCandidates?.length])

    const handleHighlightCreated = async photoId => createCategoryHighlight(photoId)
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
                name={category?.name}
                categories={category && [category]}
                highlightCandidates={highlightCandidates}
                onHighlightCandidateCreated={handleHighlightCandidateCreated} />
        </>
    )
}