import { useParams } from "react-router-dom"
import { useCategory } from "../hooks/useCategory"
import PageHeader from "../components/PageHeader"
import { useMemo } from "react"
import { useCandidatePlaces } from "../hooks/useCandidatePlaces"
import PlaceMap from "../components/PlaceMap"
import CandidatePlaceCardGrid from "../components/CandidatePlaceCardGrid"

export default function CandidateCategoryPage() {
    const { categoryId } = useParams()

    const { category, updateCategoryName } = useCategory(categoryId)
    const { candidatePlaces, removeCandidatePlace } = useCandidatePlaces({ categoryId, include: "CATEGORIES" })

    const countryCategoriesMap = useMemo(() => new Map(candidatePlaces?.map(place => place.getCategory("COUNTRY"))
        ?.filter(Boolean)?.map(category => [category.name, category])), [candidatePlaces])

    return (
        <>
            <PageHeader
                name={category?.name}
                categories={category?.metadata ? [category] : [...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))}
                onNameChanged={updateCategoryName} />
            <div className="h-[400px] md:h-[700px] my-4">
                <PlaceMap
                    places={candidatePlaces}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)} />
            </div>
            <CandidatePlaceCardGrid
                places={candidatePlaces}
                onCandidatePlaceRemoved={removeCandidatePlace} />
        </>
    )
}