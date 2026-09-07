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
import { useCategory } from "../hooks/useCategory.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { CategoryCategory, PlaceIncludedEntity, UserRole } from "../types/CoreSwaggerTypes.ts"

export default function CandidateCategoryPage() {
    const { categoryId } = useParams()
    const { showCreatePlaceToast } = usePredefinedUserInput()
    const navigate = useAppNavigate()
    const { hasRole } = useAuth()

    const { category, updateCategoryName } = useCategory(categoryId)
    const { candidatePlaces, removeCandidatePlace } = useCandidatePlaces({ categoryId, include: [PlaceIncludedEntity.Categories] })

    const countryCategoriesMap = useMemo(() => new Map(candidatePlaces?.map(place => place.getCategory(CategoryCategory.Country))
        ?.filter(Boolean)?.map(category => [category.name, category])), [candidatePlaces])

    const handleCandidatePlaceCreated = () => {
        showCreatePlaceToast((name, address) => createCandidatePlace(name, address).then(place => (navigate(place), place)))
    }

    return hasRole(UserRole.CategoryRead) && (
        <>
            <PageHeader
                name={category?.name}
                categories={category?.metadata ? [category] : [...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))}
                onNameChanged={hasRole(UserRole.CategoryEdit) && updateCategoryName} />
            <StaticMapFrame>
                <PlaceMap
                    places={candidatePlaces}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)} />
            </StaticMapFrame>
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