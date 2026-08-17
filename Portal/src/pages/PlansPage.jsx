import { useCallback, useMemo, useState } from "react"
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
import { UserRole } from "../types/CoreSwaggerTypes.ts"
import { getCurrentTimestamp } from "../utils/timeUtils.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { useFormatters } from "../hooks/useFormatters.ts"
import { useAppNavigate } from "../hooks/useAppNavigate.ts"
import RegionMap from "../components/RegionMap.jsx"
import { useRegions } from "../hooks/useRegions.ts"
import { useNavigate } from "react-router-dom"
import { useQueryParamState } from "../hooks/useQueryParamState.ts"

const defaultMaxDistance = 250
const defaultMaxQuality = 80

const TAB_URL_QUERY_PARAM_NAME = "tab"

export default function PlansPage() {
    const { hasRole } = useAuth()
    const { showCreatePlaceToast } = usePredefinedUserInput()
    const { formatKilometers } = useFormatters()
    const navigate = useAppNavigate()
    const simpleNavigate = useNavigate()
    const [selectedTab, setSelectedTab] = useQueryParamState(TAB_URL_QUERY_PARAM_NAME, "consideredPlaces")

    const { candidatePlaces, changeCurrentLocation, createCandidatePlace, removeCandidatePlace } = useCandidatePlaces({ include: ["categories"] })
    const { places: visitedPlaces } = useTimeFilteredRegularPlaces({ include: ["categories"], sort: "quality", maxEnd: getCurrentTimestamp() })
    const { trips, removeTrip } = useCandidateTrips()
    const countryCategories = useCategories({ categories: ["country"] })
    const { regions } = useRegions()

    const [maxDistance, setMaxDistance] = useState(defaultMaxDistance)
    const [maxQuality, setMaxQuality] = useState(defaultMaxQuality)

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

    const resolveColor = useCallback(region => {
        const containsRegion = place => place.categories?.some(c => c.id === region.category.id)

        const candidatePlacesInRegion = candidatePlaces?.filter(containsRegion) || []
        const visitedPlacesInRegion = visitedPlaces?.filter(containsRegion) || []

        const qualities = visitedPlacesInRegion.filter(place => place.quality).map(p => p.quality)
        const minimumQuality = Math.min(...qualities)
        const averageQuality = qualities.reduce((a, b) => a + b, 0) / qualities.length

        if (averageQuality > 0 && averageQuality < 50) {
            return "#FF0000"
        }

        if ((averageQuality >= 50 && averageQuality < 70) || minimumQuality < 70) {
            return "#FFFF00"
        }

        if (candidatePlacesInRegion.length > 0) {
            return "#9ACD32"
        }

        return "#008000"
    }, [candidatePlaces, visitedPlaces])

    const regionGeojsonsWithMetadata = useMemo(() => {
        // TODO: Add the filter to the API endpoint.
        return regions?.filter(region => region.geoJson && region.geoJson.geometry?.type !== "Point" && region.category.category === "administrative" && (visitedPlaces ?? []).some(place => place.categories?.some(c => c.id === region.category.id)))
            ?.map(region => ({
                ...region,
                geoJson: {
                    ...region.geoJson,
                    properties: {
                        ...region.geoJson.properties,
                        id: region.category.id,
                        color: resolveColor(region)
                    }
                }
            }))
    }, [regions, resolveColor])

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

    const regionsVisitedPlaces = useMemo(() => visitedPlaces?.reduce((acc, place) => {
        const administrativeCategory = place.getCategory("administrative")
        if (!administrativeCategory) {
            return acc
        }

        if (!acc[administrativeCategory.name]) {
            acc[administrativeCategory.name] = []
        }
        acc[administrativeCategory.name].push(place)
        return acc
    }, {}), [filteredVisitedPlaces])

    const tabs = [
        {
            name: "consideredPlaces",
            label: "Zvažovaná místa",
            enabled: hasRole(UserRole.PlaceRead)
        },
        {
            name: "visitedPlaces",
            label: "Navštívená místa",
            enabled: hasRole(UserRole.PlaceRead) && hasRole(UserRole.PortalFutureRead)
        },
        {
            name: "visitedRegions",
            label: "Navštívené regiony",
            enabled: hasRole(UserRole.RegionRead) && hasRole(UserRole.PortalFutureRead)
        },
        {
            name: "consideredTrips",
            label: "Návrhy výletů",
            enabled: hasRole(UserRole.TripRead) && hasRole(UserRole.PortalFutureRead)
        }
    ]

    const activeTab = useMemo(() => tabs.map(label => label.name).indexOf(selectedTab), [tabs, selectedTab])

    const handleCandidatePlaceCreated = () => {
        showCreatePlaceToast((name, address) => createCandidatePlace(name, address).then(place => (navigate(place), place)))
    }

    return tabs.some(label => label.enabled) && (
        <>
            <TabMenu
                tabs={tabs}
                selectedTab={selectedTab}
                onTabSelected={setSelectedTab} />
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
            {hasRole(UserRole.RegionRead) && hasRole(UserRole.PortalFutureRead) && activeTab === 2 && (
                <>
                    <div className="h-[400px] md:h-[700px] my-4">
                        <RegionMap
                            regions={regionGeojsonsWithMetadata}
                            onClick={categoryId => simpleNavigate("/category/" + categoryId)} />
                    </div>
                    <CategoryCardGrid
                        rowSize={5}
                        categories={regionGeojsonsWithMetadata?.map(region => region.category)}
                        categoriesPlaces={regionsVisitedPlaces} />
                </>
            )}
            {hasRole(UserRole.TripRead) && hasRole(UserRole.PortalFutureRead) && activeTab === 3 && (
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