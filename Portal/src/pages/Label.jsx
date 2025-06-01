import { useMemo } from "react"
import { useParams } from "react-router-dom"
import PageHeader from "../components/PageHeader"
import PlaceTileGrid from "../components/PlaceTileGrid"
import PlaceMap from "../components/PlaceMap"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"

export default function Label() {
    const { labelName } = useParams()

    const labelPlaces = useTimeFilteredRegularPlaces({ labelName, include: "CATEGORIES", sort: "score" })

    const countryCategoriesMap = useMemo(() => new Map(labelPlaces.map(place => place.getCategory("COUNTRY"))
        .map(category => [category.name, category])), [labelPlaces])

    return labelPlaces?.length > 0 && (
        <>
            <PageHeader
                name={labelName}
                categories={[...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))} />
            <div className="h-[400px] md:h-[700px]">
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
