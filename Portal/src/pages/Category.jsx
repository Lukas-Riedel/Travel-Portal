import { useParams } from "react-router-dom"
import { useApi } from "../hooks/useApi"
import { useCategory } from "../hooks/useCategory"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import PageHeader from "../components/PageHeader"
import HighlightCarouselAndPlaceMapToggle from "../components/HighlightCarouselAndPlaceMapToggle"
import { useAuth } from "../contexts/AuthContext"
import { getMaxEndTimestamp } from "../utils/helpers"
import PlaceTileGrid from "../components/PlaceTileGrid"
import StatisticsPanel from "../components/StatisticsPanel"
import { useMemo } from "react"

export default function Category() {
    const { categoryId } = useParams()
    const { isAdmin } = useAuth()
    const api = useApi()
    const category = useCategory(categoryId)
    // TODO: Remove the "CATEGORIES" scope
    const categoryPlaces = useRegularPlaces({ categoryId, maxEnd: getMaxEndTimestamp(isAdmin()), include: "CATEGORIES", sort: "score" })

    const countryCategoriesMap = useMemo(() => new Map(categoryPlaces.map(place => place.getCategory("COUNTRY"))
        .map(category => [category.name, category])), [categoryPlaces])

    const getPlaceCategory = place => {
        if (countryCategoriesMap.size > 1) {
            return countryCategoriesMap.get(place.country)
        }
        if (place.country === category.name) {
            return category
        }
        return place.getCategory("MOST_SPECIFIC_WITH_METADATA")
    }

    if (!category || categoryPlaces.length === 0) {
        return null
    }

    return (
        <>
            <PageHeader
                name={category.name}
                categories={category.metadata ? [category] : [...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))}
                onNameChanged={name => api.updateCategoryName(categoryId, name)} />
            <HighlightCarouselAndPlaceMapToggle
                entity={category}
                places={categoryPlaces}
                placeMainCategorySelector={getPlaceCategory}
                onHighlightRemoved={highlightId => api.removeCategoryHighlight(categoryId, highlightId)}
                onMainHighlightUpdated={highlightId => api.updateCategoryMainHighlight(categoryId, highlightId)} />
            <StatisticsPanel statistics={category.statistics} />
            <PlaceTileGrid
                places={categoryPlaces}
                placeMainCategorySelector={getPlaceCategory} />
        </>
    )
}