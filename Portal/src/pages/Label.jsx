import { useEffect, useMemo, useState } from "react"
import { useParams } from "react-router-dom"
import PageHeader from "../components/PageHeader"
import { useAuth } from "../contexts/AuthContext"
import { getMaxEndTimestamp } from "../utils/helpers"
import PlaceTileGrid from "../components/PlaceTileGrid"
import PlaceMap from "../components/PlaceMap"
import { useRegularPlaces } from "../hooks/useRegularPlaces"

export default function Label() {
    const { labelName } = useParams()
    const { isAdmin } = useAuth()
    // TODO: Remove the "CATEGORIES" scope
    const labelPlaces = useRegularPlaces({ labelName, maxEnd: getMaxEndTimestamp(isAdmin()), include: "CATEGORIES", sort: "score" })

    const countryCategoriesMap = useMemo(() => new Map(labelPlaces.map(place => place.getCategory("COUNTRY"))
        .map(category => [category.name, category])), [labelPlaces])

    if (labelPlaces.length === 0) {
        return null
    }

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
