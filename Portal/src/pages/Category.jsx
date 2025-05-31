import { useEffect, useState } from "react"
import { useParams } from "react-router-dom"
import { useApi } from "../hooks/useApi"
import PageHeader from "../components/PageHeader"
import HighlightCarouselAndPlaceMapToggle from "../components/HighlightCarouselAndPlaceMapToggle"
import { useAuth } from "../contexts/AuthContext"
import { getMaxEndTimestamp } from "../utils/helpers"
import PlaceTileGrid from "../components/PlaceTileGrid"

export default function Category() {
    const { categoryId } = useParams()
    const { isAdmin } = useAuth()
    const api = useApi()
    const [category, setCategory] = useState(null)
    const [categoryPlaces, setCategoryPlaces] = useState([])

    const fetchAndSetCategory = () => api.getCategory(categoryId)
        .then(setCategory)
        .catch(console.error)

    // TODO: Remove the "DATES" scope after moving score to backend functions
    const fetchAndSetCategoryPlaces = () => api.listRegularPlaces(undefined, categoryId, undefined, undefined, undefined, getMaxEndTimestamp(isAdmin()), "CATEGORIES,DATES")
        .then(places => places.sort((a, b) => b.imagesScore - a.imagesScore))
        .then(setCategoryPlaces)
        .catch(console.error)

    useEffect(() => {
        setCategory(null)
        fetchAndSetCategory()
    }, [categoryId])

    useEffect(() => {
        setCategoryPlaces([])
        fetchAndSetCategoryPlaces()
    }, [categoryId])

    if (!category || categoryPlaces.length === 0) {
        return null
    }

    const countryCategoriesMap = new Map(categoryPlaces.map(place => place.getCategory("COUNTRY"))
        .map(category => [category.name, category]))
    const getPlaceCategory = place => {
        if (countryCategoriesMap.size > 1) {
            return countryCategoriesMap.get(place.country)
        }
        if (place.country === category.name) {
            return category
        }
        return place.getCategory("MOST_SPECIFIC_WITH_METADATA")
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
            <PlaceTileGrid places={categoryPlaces}
                placeMainCategorySelector={getPlaceCategory} />
        </>
    )
}