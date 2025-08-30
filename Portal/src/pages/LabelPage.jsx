import { useMemo } from "react"
import { useParams } from "react-router-dom"
import PageHeader from "../components/PageHeader"
import PlaceTileGrid from "../components/PlaceTileGrid"
import PlaceMap from "../components/PlaceMap"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"
import { useLabel } from "../hooks/useLabel"

export default function LabelPage() {
    const { labelId } = useParams()

    const { label, updateLabelName } = useLabel(labelId)
    const labelPlaces = useTimeFilteredRegularPlaces({ labelId, include: "categories", sort: "-score" })

    const countryCategoriesMap = useMemo(() => new Map(labelPlaces?.map(place => place.getCategory("country"))
        ?.filter(Boolean)?.map(category => [category.name, category])), [labelPlaces])

    return (
        <>
            <PageHeader
                name={label?.name}
                onNameChanged={updateLabelName}
                categories={[...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))} />
            <div className="h-[400px] md:h-[700px] my-4">
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
