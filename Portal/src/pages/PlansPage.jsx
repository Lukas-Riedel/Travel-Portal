import { useMemo, useState } from "react"
import { useCandidatePlaces } from "../hooks/useCandidatePlaces"
import { useCategories } from "../hooks/useCategories"
import PlaceMap from "../components/PlaceMap"
import CategoryCardGrid from "../components/CategoryCardGrid"
import TripCardGrid from "../components/TripCardGrid"
import Slider from "../components/Slider"
import { formatKilometers } from "../utils/formatters"
import { useCandidateTrips } from "../hooks/useCandidateTrips"
import { useAuth } from "../contexts/AuthContext"
import showFormToast from "../components/FormToast"
import FloatingButton from "../components/FloatingButton"
import { Plus } from "lucide-react"
import TabMenu from "../components/TabMenu"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { endOfDay } from "date-fns"

const defaultMaxDistance = 250
const defaultMaxQuality = 80

export default function PlansPage() {
    const { isAdmin } = useAuth()

    const { candidatePlaces, changeCurrentLocation, createCandidatePlace, removeCandidatePlace } = useCandidatePlaces({ include: ["categories"] })
    const { places: visitedPlaces } = useRegularPlaces({ maxEnd: Math.round(endOfDay(new Date()).getTime() / 1000), sort: "quality" })
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
            name: "Zvažovaná místa",
            enabled: true
        },
        {
            name: "Navštívená místa",
            enabled: isAdmin
        },
        {
            name: "Návrhy výletů",
            enabled: isAdmin
        }
    ]

    const handleCandidatePlaceCreated = () => {
        showFormToast(
            "Zadej údaje o místě k přidání:",
            [
                { label: "Jméno", required: true },
                { label: "Adresa", required: false }
            ],
            "Místo bylo úspěšně přidáno",
            "Při přidávání místa došlo k chybě",
            async (name, address) => createCandidatePlace(name, address || name)
        )
    }

    return (
        <>
            <TabMenu
                labels={labels}
                onActiveTabChanged={setActiveTab} />
            {activeTab === 0 && (
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
                        categories={countryCategories}
                        categoriesPlaces={countriesCandidatePlaces}
                        onCurrentLocationChanged={changeCurrentLocation}
                        onMaximumDistanceChanged={setMaxDistance}
                        onPlaceRemoved={removeCandidatePlace} />
                </>
            )}
            {activeTab === 1 && (
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
                        categories={countryCategories}
                        categoriesPlaces={countriesVisitedPlaces} />
                </>
            )}
            {activeTab === 2 && (
                <TripCardGrid
                    trips={trips}
                    onTripRemoved={removeTrip} />
            )}
            {isAdmin && (
                <FloatingButton
                    icon={Plus}
                    onClick={handleCandidatePlaceCreated} />
            )}
        </>
    )
}