import { useMemo, useState } from "react"
import { useCandidatePlaces } from "../hooks/useCandidatePlaces"
import { useCategories } from "../hooks/useCategories"
import PlaceMap from "../components/PlaceMap"
import CandidateCategoryCardGrid from "../components/CandidateCategoryCardGrid"
import AddPlaceCandidateFloatingButton from "../components/AddPlaceCandidateFloatingButton"
import Slider from "../components/Slider"
import { formatKilometers } from "../utils/formatters"

export default function PlansPage() {
    const { candidatePlaces, changeCurrentLocation, createCandidatePlace, removeCandidatePlace } = useCandidatePlaces({ include: "CATEGORIES" })
    const countryCategories = useCategories({ categories: "COUNTRY" })

    const [maxDistance, setMaxDistance] = useState(250)

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

    const filteredPlaces = useMemo(() => candidatePlaces?.filter(place => !place.distance || place.distance <= maxDistance), [candidatePlaces, maxDistance])
    const furthestPlace = useMemo(() => candidatePlaces?.filter(place => place.distance)?.reduce((max, place) => !max || place.distance > max.distance ? place : max, undefined), [candidatePlaces])

    const countriesPlaces = useMemo(() => filteredPlaces?.reduce((acc, place) => {
        if (!acc[place.country]) {
            acc[place.country] = []
        }
        acc[place.country].push(place)
        return acc
    }, {}), [filteredPlaces])

    return (
        <>
            <div className="h-[400px] md:h-[700px] my-4">
                <PlaceMap
                    places={filteredPlaces}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)} />
            </div>
            {furthestPlace && (
                <Slider
                    name="Maximální vzdálenost"
                    valueFormatter={formatKilometers}
                    value={maxDistance}
                    minValue={1}
                    maxValue={furthestPlace.distance}
                    onValueChanged={setMaxDistance} />
            )}
            <CandidateCategoryCardGrid
                categories={countryCategories}
                categoriesPlaces={countriesPlaces}
                onCurrentLocationChanged={changeCurrentLocation}
                onMaximumDistanceChanged={setMaxDistance}
                onCandidatePlaceRemoved={removeCandidatePlace} />
            <AddPlaceCandidateFloatingButton onCandidatePlaceCreated={createCandidatePlace} />
        </>
    )
}