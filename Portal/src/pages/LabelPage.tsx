import { useMemo } from "react"
import StaticMapFrame from "../components/StaticMapFrame.tsx"
import { useParams } from "react-router-dom"
import PageHeader from "../components/PageHeader"
import PlaceTileGrid from "../components/PlaceTileGrid"
import PlaceMap from "../components/PlaceMap"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"
import { useLabel } from "../hooks/useLabel"
import { useAuth } from "../contexts/AuthContext.tsx"
import { CategoryCategory, PlaceIncludedEntity, PlaceSortingStrategy, UserRole } from "../types/CoreSwaggerTypes.ts"
import { Folder } from "lucide-react"
import AppLink from "../components/AppLink.tsx"
import { AppLinkTarget } from "../types/AppLinkTarget.ts"

export default function LabelPage() {
    const { labelId } = useParams()
    const { hasRole } = useAuth()

    const { label, updateLabelName } = useLabel(labelId)
    const { places } = useTimeFilteredRegularPlaces({ labelId, include: [PlaceIncludedEntity.Categories], sort: PlaceSortingStrategy.ValueScore })

    const countryCategoriesMap = useMemo(() => new Map(places?.map(place => place.getCategory(CategoryCategory.Country))
        ?.filter(Boolean)?.map(category => [category.name, category])), [places])

    return hasRole(UserRole.LabelRead) && (
        <>
            <PageHeader
                name={label?.name}
                onNameChanged={hasRole(UserRole.LabelEdit) && updateLabelName}
                categories={[...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))} />
            <StaticMapFrame>
                <PlaceMap
                    places={places}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)}
                />
            </StaticMapFrame>
            <PlaceTileGrid
                places={places}
                placeMainCategorySelector={place => countryCategoriesMap.get(place.country)} />
            <div className="flex justify-end">
                <div className="flex items-center gap-2">
                    {label && (
                        <AppLink
                            target={AppLinkTarget.Plans}
                            to={label}
                            className="btn-chip-gray">
                            <Folder size={16} />
                        </AppLink>
                    )}
                </div>
            </div>
        </>
    )
}
