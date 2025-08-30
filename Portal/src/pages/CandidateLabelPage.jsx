import { useMemo } from "react"
import { useParams } from "react-router-dom"
import PageHeader from "../components/PageHeader"
import PlaceMap from "../components/PlaceMap"
import { useCandidatePlaces } from "../hooks/useCandidatePlaces"
import PlaceCardGrid from "../components/PlaceCardGrid"
import { useLabel } from "../hooks/useLabel"

export default function LabelPage() {
    const { labelId } = useParams()

    const { label, updateLabelName } = useLabel(labelId)
    const { candidatePlaces, removeCandidatePlace } = useCandidatePlaces({ labelId, include: "categories" })

    const countryCategoriesMap = useMemo(() => new Map(candidatePlaces?.map(place => place.getCategory("country"))
        ?.filter(Boolean)?.map(category => [category.name, category])), [candidatePlaces])

    return (
        <>
            <PageHeader
                name={label?.name}
                onNameChanged={updateLabelName}
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
