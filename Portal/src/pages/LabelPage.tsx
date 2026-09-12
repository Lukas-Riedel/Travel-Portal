import { Edit2, Folder } from "lucide-react"
import { useParams } from "react-router-dom"

import AppLink from "../components/AppLink.tsx"
import PageHeader from "../components/PageHeader"
import PlaceMap from "../components/PlaceMap"
import PlaceTileGrid from "../components/PlaceTileGrid"
import StaticMapFrame from "../components/StaticMapFrame.tsx"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useLabel } from "../hooks/useLabel"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"
import { AppLinkTarget } from "../types/AppLinkTarget.ts"
import { CategoryCategory, PlaceIncludedEntity, PlaceSortingStrategy, UserRole } from "../types/CoreSwaggerTypes.ts"
import { getPlaceCategory } from "../utils/placeUtils.ts"

export default function LabelPage() {
    const { labelId } = useParams()
    const { hasRole } = useAuth()
    const { showUpdateLabelToast } = usePredefinedUserInput()

    const { label, updateLabelName, updateLabelMetadata } = useLabel(labelId)
    const { places } = useTimeFilteredRegularPlaces({ labelId, include: [PlaceIncludedEntity.Categories], sort: PlaceSortingStrategy.ValueScore })

    const countryCategoriesMap = new Map(places?.map(place => getPlaceCategory(place, CategoryCategory.Country))
        ?.filter((c): c is NonNullable<typeof c> => c != null)?.map(category => [category.name, category]))

    const handleMetadataChanged = () => {
        if (label) {
            showUpdateLabelToast(label, updateLabelMetadata)
        }
    }

    return hasRole(UserRole.LabelRead) && (
        <>
            <PageHeader
                name={label?.name ?? null}
                onNameChanged={hasRole(UserRole.LabelEdit) ? updateLabelName : undefined}
                categories={[...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))} />
            <StaticMapFrame>
                <PlaceMap
                    places={places}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country ?? "") ?? null}
                />
            </StaticMapFrame>
            <PlaceTileGrid
                places={places}
                placeMainCategorySelector={place => countryCategoriesMap.get(place.country ?? "") ?? null} />
            <div className="flex justify-end">
                <div className="flex items-center gap-2">
                    {label && (
                        <AppLink
                            target={AppLinkTarget.Plans}
                            to={label}
                            className="btn-chip">
                            <Folder size={16} />
                        </AppLink>
                    )}
                    {hasRole(UserRole.LabelEdit) && (
                        <button
                            onClick={handleMetadataChanged}
                            className="btn-chip">
                            <Edit2 size={16} />
                        </button>
                    )}
                </div>
            </div>
        </>
    )
}
