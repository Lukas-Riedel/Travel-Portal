import { useParams } from "react-router-dom"
import { useCategory } from "../hooks/useCategory"
import PageHeader from "../components/PageHeader"
import HighlightCarouselAndPlaceMapToggle from "../components/HighlightCarouselAndPlaceMapToggle"
import PlaceTileGrid from "../components/PlaceTileGrid"
import StatisticsPanel from "../components/StatisticsPanel"
import { useMemo } from "react"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"

export default function Category() {
    const { categoryId } = useParams()

    const { category, updateCategoryName, removeCategoryHighlight, updateCategoryMainHighlight } = useCategory(categoryId)
    const categoryPlaces = useTimeFilteredRegularPlaces({ categoryId, include: "CATEGORIES", sort: "score" })

    const countryCategoriesMap = useMemo(() => new Map(categoryPlaces?.map(place => place.getCategory("COUNTRY"))
        ?.filter(Boolean)?.map(category => [category.name, category])), [categoryPlaces])

    const getPlaceCategory = place => {
        if (countryCategoriesMap.size > 1) {
            return countryCategoriesMap.get(place?.country)
        }
        if (place?.country === category?.name) {
            return category
        }
        return place?.getCategory("MOST_SPECIFIC_WITH_METADATA")
    }

    return (
        <>
            <PageHeader
                name={category?.name}
                categories={category?.metadata ? [category] : [...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))}
                onNameChanged={updateCategoryName} />
            <HighlightCarouselAndPlaceMapToggle
                entity={category}
                places={categoryPlaces}
                placeMainCategorySelector={getPlaceCategory}
                onHighlightRemoved={removeCategoryHighlight}
                onMainHighlightUpdated={updateCategoryMainHighlight} />
            <StatisticsPanel statistics={category?.statistics} />
            <PlaceTileGrid
                places={categoryPlaces}
                placeMainCategorySelector={getPlaceCategory} />
        </>
    )
}