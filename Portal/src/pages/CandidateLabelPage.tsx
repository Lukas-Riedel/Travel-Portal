import { Plus } from "lucide-react"
import { useMemo } from "react"
import { useParams } from "react-router-dom"

import { createCandidatePlace } from "../clients/coreClient.ts"
import FloatingButton from "../components/FloatingButton.js"
import PageHeader from "../components/PageHeader.tsx"
import PlaceCardGrid from "../components/PlaceCardGrid.tsx"
import PlaceMap from "../components/PlaceMap.tsx"
import StaticMapFrame from "../components/StaticMapFrame.tsx"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useAppNavigate } from "../hooks/useAppNavigate.ts"
import { useCandidatePlaces } from "../hooks/useCandidatePlaces.ts"
import { useLabel } from "../hooks/useLabel.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { CategoryCategory, PlaceIncludedEntity, UserRole } from "../types/CoreSwaggerTypes.ts"

export default function CandidateLabelPage() {
    const { labelId } = useParams()
    const { showCreatePlaceToast } = usePredefinedUserInput()
    const navigate = useAppNavigate()
    const { hasRole } = useAuth()

    const { label, updateLabelName } = useLabel(labelId)
    const { candidatePlaces, removeCandidatePlace } = useCandidatePlaces({ labelId, include: [PlaceIncludedEntity.Categories] })

    const countryCategoriesMap = useMemo(() => new Map(candidatePlaces?.map(place => place.getCategory(CategoryCategory.Country))
        ?.filter((c): c is NonNullable<typeof c> => c != null)?.map(category => [category.name, category])), [candidatePlaces])

    const handleCandidatePlaceCreated = () => {
        showCreatePlaceToast((name, address) => createCandidatePlace(name, address).then(place => (navigate(place), place)))
    }

    return hasRole(UserRole.LabelRead) && (
        <>
            <PageHeader
                name={label?.name ?? null}
                onNameChanged={hasRole(UserRole.LabelEdit) ? updateLabelName : undefined}
                categories={[...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))} />
            <StaticMapFrame>
                <PlaceMap
                    places={candidatePlaces ?? null}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country) ?? null}
                />
            </StaticMapFrame>
            <PlaceCardGrid
                places={candidatePlaces ?? null}
                rowSize={5}
                onPlaceRemoved={hasRole(UserRole.PlaceEdit) ? removeCandidatePlace : undefined} />
            {hasRole(UserRole.PlaceEdit) && (
                <FloatingButton
                    icon={Plus}
                    onClick={handleCandidatePlaceCreated} />
            )}
        </>
    )
}
