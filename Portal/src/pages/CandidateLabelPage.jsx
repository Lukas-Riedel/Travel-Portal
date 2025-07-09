import { useMemo } from "react"
import { useParams } from "react-router-dom"
import PageHeader from "../components/PageHeader"
import PlaceMap from "../components/PlaceMap"
import { useCandidatePlaces } from "../hooks/useCandidatePlaces"
import PlaceCardGrid from "../components/PlaceCardGrid"

export default function LabelPage() {
    const { labelName } = useParams()

    const { candidatePlaces, removeCandidatePlace } = useCandidatePlaces({ labelName, include: "CATEGORIES" })

    const countryCategoriesMap = useMemo(() => new Map(candidatePlaces?.map(place => place.getCategory("COUNTRY"))
        ?.filter(Boolean)?.map(category => [category.name, category])), [candidatePlaces])

    return (
        <>
            <PageHeader
                name={labelName}
                categories={[...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))} />
            <div className="h-[400px] md:h-[700px] my-4">
                <PlaceMap
                    places={candidatePlaces}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)}
                />
            </div>
            <PlaceCardGrid
                places={candidatePlaces}
                onPlaceRemoved={removeCandidatePlace} />
        </>
    )
}
