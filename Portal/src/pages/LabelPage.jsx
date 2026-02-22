import { useMemo } from "react"
import { useParams } from "react-router-dom"
import PageHeader from "../components/PageHeader"
import PlaceTileGrid from "../components/PlaceTileGrid"
import PlaceMap from "../components/PlaceMap"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"
import { useLabel } from "../hooks/useLabel"
import { useAuth } from "../contexts/AuthContext.tsx"
import { UserRole } from "../types/CoreSwaggerTypes.ts"

export default function LabelPage() {
    const { labelId } = useParams()
    const { hasRole } = useAuth()

    const { label, updateLabelName } = useLabel(labelId)
    const { places } = useTimeFilteredRegularPlaces({ labelId, include: ["categories"], sort: "-score" })

    const countryCategoriesMap = useMemo(() => new Map(places?.map(place => place.getCategory("country"))
        ?.filter(Boolean)?.map(category => [category.name, category])), [places])

    return hasRole(UserRole.LabelRead) && (
        <>
            <PageHeader
                name={label?.name}
                onNameChanged={hasRole(UserRole.LabelEdit) && updateLabelName}
                categories={[...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))} />
            <div className="h-[400px] md:h-[700px] my-4">
                <PlaceMap
                    places={places}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)}
                />
            </div>
            <PlaceTileGrid
                places={places}
                placeMainCategorySelector={place => countryCategoriesMap.get(place.country)} />
        </>
    )
}
