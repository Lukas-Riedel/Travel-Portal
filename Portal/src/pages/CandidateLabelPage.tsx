import { useMemo } from "react"
import { useParams } from "react-router-dom"
import PageHeader from "../components/PageHeader.tsx"
import PlaceMap from "../components/PlaceMap.tsx"
import { useCandidatePlaces } from "../hooks/useCandidatePlaces.ts"
import PlaceCardGrid from "../components/PlaceCardGrid.tsx"
import { useLabel } from "../hooks/useLabel.ts"
import { useAuth } from "../contexts/AuthContext.tsx"
import { CategoryCategory, PlaceIncludedEntity, UserRole } from "../types/CoreSwaggerTypes.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { useAppNavigate } from "../hooks/useAppNavigate.ts"
import FloatingButton from "../components/FloatingButton.js"
import { Plus } from "lucide-react"
import { createCandidatePlace } from "../clients/coreClient.ts"

export default function CandidateLabelPage() {
    const { labelId } = useParams()
    const { showCreatePlaceToast } = usePredefinedUserInput()
    const navigate = useAppNavigate()
    const { hasRole } = useAuth()

    const { label, updateLabelName } = useLabel(labelId)
    const { candidatePlaces, removeCandidatePlace } = useCandidatePlaces({ labelId, include: [PlaceIncludedEntity.Categories] })

    const countryCategoriesMap = useMemo(() => new Map(candidatePlaces?.map(place => place.getCategory(CategoryCategory.Country))
        ?.filter(Boolean)?.map(category => [category.name, category])), [candidatePlaces])

    const handleCandidatePlaceCreated = () => {
        showCreatePlaceToast((name, address) => createCandidatePlace(name, address).then(place => (navigate(place), place)))
    }

    return hasRole(UserRole.LabelRead) && (
        <>
            <PageHeader
                name={label?.name}
                onNameChanged={hasRole(UserRole.LabelEdit) && updateLabelName}
                categories={[...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))} />
            <div className="h-[400px] md:h-[700px] my-4">
                <PlaceMap
                    places={candidatePlaces}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)}
                />
            </div>
            <PlaceCardGrid
                places={candidatePlaces}
                rowSize={5}
                onPlaceRemoved={hasRole(UserRole.PlaceEdit) && removeCandidatePlace} />
            {hasRole(UserRole.PlaceEdit) && (
                <FloatingButton
                    icon={Plus}
                    onClick={handleCandidatePlaceCreated} />
            )}
        </>
    )
}
