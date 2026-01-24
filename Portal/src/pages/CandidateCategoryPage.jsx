import { useParams } from "react-router-dom"
import { useCategory } from "../hooks/useCategory"
import PageHeader from "../components/PageHeader"
import { useMemo } from "react"
import { useCandidatePlaces } from "../hooks/useCandidatePlaces"
import PlaceMap from "../components/PlaceMap"
import PlaceCardGrid from "../components/PlaceCardGrid"
import { useAuth } from "../contexts/AuthContext.tsx"
import { UserRole } from "../types/CoreSwaggerTypes.ts"

export default function CandidateCategoryPage() {
    const { categoryId } = useParams()
    const { hasRole } = useAuth()

    const { category, updateCategoryName } = useCategory(categoryId)
    const { candidatePlaces, removeCandidatePlace } = useCandidatePlaces({ categoryId, include: ["categories"] })

    const countryCategoriesMap = useMemo(() => new Map(candidatePlaces?.map(place => place.getCategory("country"))
        ?.filter(Boolean)?.map(category => [category.name, category])), [candidatePlaces])

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
                onPlaceRemoved={hasRole(UserRole.PlaceEdit) && removeCandidatePlace} />
        </>
    )
}