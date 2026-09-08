import { Folder } from "lucide-react"
import { useMemo } from "react"
import { useParams } from "react-router-dom"

import AppLink from "../components/AppLink.tsx"
import PageHeader from "../components/PageHeader"
import PlaceMap from "../components/PlaceMap"
import PlaceTileGrid from "../components/PlaceTileGrid"
import StaticMapFrame from "../components/StaticMapFrame.tsx"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useLabel } from "../hooks/useLabel"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"
import { AppLinkTarget } from "../types/AppLinkTarget.ts"
import { CategoryCategory, PlaceIncludedEntity, PlaceSortingStrategy, UserRole } from "../types/CoreSwaggerTypes.ts"

export default function LabelPage() {
    const { labelId } = useParams()
    const { hasRole } = useAuth()

    const { label, updateLabelName } = useLabel(labelId)
    const { places } = useTimeFilteredRegularPlaces({ labelId, include: [PlaceIncludedEntity.Categories], sort: PlaceSortingStrategy.ValueScore })

    const countryCategoriesMap = useMemo(() => new Map(places?.map(place => place.getCategory(CategoryCategory.Country))
        ?.filter((c): c is NonNullable<typeof c> => c != null)?.map(category => [category.name, category])), [places])

    return hasRole(UserRole.LabelRead) && (
        <>
            <PageHeader
                name={label?.name ?? null}
                onNameChanged={hasRole(UserRole.LabelEdit) ? updateLabelName : undefined}
                categories={[...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))} />
            <StaticMapFrame>
                <PlaceMap
                    places={places ?? null}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country) ?? null}
                />
            </StaticMapFrame>
            <PlaceTileGrid
                places={places ?? null}
                placeMainCategorySelector={place => countryCategoriesMap.get(place.country) ?? null} />
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
