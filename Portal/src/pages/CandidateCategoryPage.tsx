import { useParams } from "react-router-dom"
import { useCategory } from "../hooks/useCategory.ts"
import PageHeader from "../components/PageHeader.tsx"
import { useMemo } from "react"
import { useCandidatePlaces } from "../hooks/useCandidatePlaces.ts"
import PlaceMap from "../components/PlaceMap.tsx"
import PlaceCardGrid from "../components/PlaceCardGrid.tsx"
import { useAuth } from "../contexts/AuthContext.tsx"
import { CategoryCategory, PlaceIncludedEntity, UserRole } from "../types/CoreSwaggerTypes.ts"
import FloatingButton from "../components/FloatingButton.js"
import { createCandidatePlace } from "../clients/coreClient.ts"
import { useAppNavigate } from "../hooks/useAppNavigate.ts"
import { Plus } from "lucide-react"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"

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
            <div className="h-[400px] md:h-[700px] my-4">
                <PlaceMap
                    places={candidatePlaces}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)} />
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