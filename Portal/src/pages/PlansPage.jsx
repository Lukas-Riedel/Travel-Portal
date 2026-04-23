import { useMemo, useState } from "react"
import { useCandidatePlaces } from "../hooks/useCandidatePlaces"
import { useCategories } from "../hooks/useCategories"
import PlaceMap from "../components/PlaceMap"
import CategoryCardGrid from "../components/CategoryCardGrid"
import TripCardGrid from "../components/TripCardGrid"
import Slider from "../components/Slider"
import { useCandidateTrips } from "../hooks/useCandidateTrips"
import { useAuth } from "../contexts/AuthContext"
import FloatingButton from "../components/FloatingButton"
import { Plus } from "lucide-react"
import TabMenu from "../components/TabMenu"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"
import { useUserInput } from "../hooks/useUserInput.tsx"
import { UserRole } from "../types/CoreSwaggerTypes.ts"
import { getCurrentTimestamp } from "../utils/timeUtils.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { useFormatters } from "../hooks/useFormatters.ts"
import { useAppNavigate } from "../hooks/useAppNavigate.ts"

const defaultMaxDistance = 250
const defaultMaxQuality = 80

export default function PlansPage() {
    const { hasRole } = useAuth()
    const { showCreatePlaceToast } = usePredefinedUserInput()
    const { formatKilometers } = useFormatters()
    const navigate = useAppNavigate()

    const { candidatePlaces, changeCurrentLocation, createCandidatePlace, removeCandidatePlace } = useCandidatePlaces({ include: ["categories"] })
    const { places: visitedPlaces } = useTimeFilteredRegularPlaces({ sort: "quality", maxEnd: getCurrentTimestamp() })
    const { trips, removeTrip } = useCandidateTrips()
    const countryCategories = useCategories({ categories: ["country"] })

    const [maxDistance, setMaxDistance] = useState(defaultMaxDistance)
    const [maxQuality, setMaxQuality] = useState(defaultMaxQuality)
    const [activeTab, setActiveTab] = useState(0)

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

    const filteredCandidatePlaces = useMemo(() => candidatePlaces?.filter(place => !place.distance || place.distance <= maxDistance), [candidatePlaces, maxDistance])
    const furthestPlace = useMemo(() => candidatePlaces?.filter(place => place.distance)?.reduce((max, place) => !max || place.distance > max.distance ? place : max, undefined), [candidatePlaces])

    const countriesCandidatePlaces = useMemo(() => filteredCandidatePlaces?.reduce((acc, place) => {
        if (!acc[place.country]) {
            acc[place.country] = []
        }
        acc[place.country].push(place)
        return acc
    }, {}), [filteredCandidatePlaces])

    const filteredVisitedPlaces = useMemo(() => visitedPlaces?.filter(place => !place.quality || place.quality <= maxQuality), [visitedPlaces, maxQuality])
    const lowestQualityPlace = useMemo(() => visitedPlaces?.filter(place => place?.quality ?? 0)?.reduce((min, place) => !min || place.quality < min.quality ? place : min, undefined), [visitedPlaces])

    const countriesVisitedPlaces = useMemo(() => filteredVisitedPlaces?.reduce((acc, place) => {
        if (!acc[place.country]) {
            acc[place.country] = []
        }
        acc[place.country].push(place)
        return acc
    }, {}), [filteredVisitedPlaces])

    const labels = [
        {
            tab: "considered",
            name: "Zvažovaná místa",
            enabled: hasRole(UserRole.PlaceRead)
        },
        {
            tab: "visited",
            name: "Navštívená místa",
            enabled: hasRole(UserRole.PlaceRead) && hasRole(UserRole.PortalFutureRead)
        },
        {
            tab: "trips",
            name: "Návrhy výletů",
            enabled: hasRole(UserRole.TripRead) && hasRole(UserRole.PortalFutureRead)
        }
    ]

    const handleCandidatePlaceCreated = () => {
        showCreatePlaceToast((name, address) => createCandidatePlace(name, address).then(place => (navigate(place), place)))
    }

    return labels.some(label => label.enabled) && (
        <>
            <TabMenu
                labels={labels}
                onActiveTabChanged={setActiveTab} />
            {hasRole(UserRole.PlaceRead) && activeTab === 0 && (
                <>
                    <div className="h-[400px] md:h-[700px] my-4">
                        <PlaceMap
                            places={filteredCandidatePlaces}
                            placeMainCategorySelector={place => countryCategoriesMap.get(place.country)} />
                    </div>
                    {furthestPlace && (
                        <Slider
                            name="Maximální vzdálenost"
                            valueFormatter={formatKilometers}
                            value={maxDistance}
                            defaultValue={defaultMaxDistance}
                            minValue={1}
                            maxValue={furthestPlace?.distance}
                            onValueChanged={setMaxDistance} />
                    )}
                    <CategoryCardGrid
                        rowSize={5}
                        categories={countryCategories}
                        categoriesPlaces={countriesCandidatePlaces}
                        onCurrentLocationChanged={changeCurrentLocation}
                        onMaximumDistanceChanged={setMaxDistance}
                        onPlaceRemoved={hasRole(UserRole.PlaceEdit) && removeCandidatePlace} />
                </>
            )}
            {hasRole(UserRole.PlaceRead) && hasRole(UserRole.PortalFutureRead) && activeTab === 1 && (
                <>
                    <div className="h-[400px] md:h-[700px] my-4">
                        <PlaceMap
                            places={filteredVisitedPlaces}
                            placeMainCategorySelector={place => countryCategoriesMap.get(place.country)} />
                    </div>
                    <Slider
                        name="Maximální kvalita"
                        valueFormatter={value => `${value}%`}
                        value={maxQuality}
                        defaultValue={defaultMaxQuality}
                        minValue={Math.ceil(lowestQualityPlace?.quality ?? 0)}
                        maxValue={100}
                        onValueChanged={setMaxQuality} />
                    <CategoryCardGrid
                        rowSize={5}
                        categories={countryCategories}
                        categoriesPlaces={countriesVisitedPlaces} />
                </>
            )}
            {hasRole(UserRole.TripRead) && hasRole(UserRole.PortalFutureRead) && activeTab === 2 && (
                <TripCardGrid
                    rowSize={3}
                    trips={trips}
                    onTripRemoved={hasRole(UserRole.TripEdit) && removeTrip} />
            )}
            {hasRole(UserRole.PlaceEdit) && (
                <FloatingButton
                    icon={Plus}
                    onClick={handleCandidatePlaceCreated} />
            )}
        </>
    )
}