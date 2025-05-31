import { useEffect, useState } from "react"
import { useParams } from "react-router-dom"
import { useApi } from "../hooks/useApi"
import PageHeader from "../components/PageHeader"
import HighlightCarouselAndPlaceMapToggle from "../components/HighlightCarouselAndPlaceMapToggle"
import { useAuth } from "../contexts/AuthContext"
import { getMaxEndTimestamp } from "../utils/helpers"
import PlaceTileGrid from "../components/PlaceTileGrid"
import StatisticsPanel from "../components/StatisticsPanel"
import PlaceMap from "../components/PlaceMap"

export default function Label() {
    const { labelName } = useParams()
    const { isAdmin } = useAuth()
    const api = useApi()
    const [labelPlaces, setLabelPlaces] = useState([])

    // TODO: Remove the "DATES" scope after moving score to backend functions
    const fetchAndSetLabelPlaces = () => api.listRegularPlaces(undefined, undefined, labelName, undefined, undefined, getMaxEndTimestamp(isAdmin()), "CATEGORIES,DATES")
        .then(places => places.sort((a, b) => b.imagesScore - a.imagesScore))
        .then(setLabelPlaces)
        .catch(console.error)

    useEffect(() => {
        setLabelPlaces([])
        fetchAndSetLabelPlaces()
    }, [labelName])

    if (labelPlaces.length === 0) {
        return null
    }

    const countryCategoriesMap = new Map(labelPlaces.map(place => place.getCategory("COUNTRY"))
        .map(category => [category.name, category]))

    return (
        <>
            <PageHeader
                name={labelName}
                categories={[...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))} />
            <div className="h-[700px]">
                <PlaceMap
                    places={labelPlaces}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)}
                />
            </div>
            <PlaceTileGrid
                places={labelPlaces}
                placeMainCategorySelector={place => countryCategoriesMap.get(place.country)} />
        </>
    )
}
