import { useCallback, useMemo, useState } from "react"
import { useAuth } from "../contexts/AuthContext"
import { useTranslation } from "react-i18next"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { useFormatters } from "../hooks/useFormatters.ts"
import { useAppNavigate } from "../hooks/useAppNavigate.ts"
import { useNavigate } from "react-router-dom"
import { useQueryParamState } from "../hooks/useQueryParamState.ts"
import { useCandidatePlaces } from "../hooks/useCandidatePlaces"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"
import { useCandidateTrips } from "../hooks/useCandidateTrips"
import { useRegions } from "../hooks/useRegions.ts"
import StaticMapFrame from "../components/StaticMapFrame.tsx"
import PlaceMap from "../components/PlaceMap"
import CategoryCardGrid from "../components/CategoryCardGrid"
import TripCardGrid from "../components/TripCardGrid"
import Slider from "../components/Slider"
import FloatingButton from "../components/FloatingButton"
import { Plus } from "lucide-react"
import TabMenu from "../components/TabMenu"
import RegionMap from "../components/RegionMap.jsx"
import { CategoryCategory, PlaceIncludedEntity, PlaceSortingStrategy, UserRole, type CompositeRegion, type GeographicalRegion } from "../types/CoreSwaggerTypes.ts"
import { PlansMenuTabName } from "../types/PlansMenuTabName.ts"
import { getCurrentOrMaximumAllowedTimestamp, getCurrentTimestamp } from "../utils/timeUtils.ts"
import { useCountryCategoriesMap } from "../hooks/useCountryCategoriesMap.ts"
import type { Feature, GeoJSON } from "geojson"
import type { Place } from "../classes/Place.ts"

const DEFAULT_MAX_DISTANCE = 250
const DEFAULT_MAX_QUALITY = 80

const INSUFFICIENT_QUALITY_THRESHOLD = 50
const AVERAGE_QUALITY_THRESHOLD = 70

const TAB_URL_QUERY_PARAM_NAME = "tab"

const isGeographicalRegion = (region: GeographicalRegion | CompositeRegion): region is GeographicalRegion => (region as GeographicalRegion)?.geoJson !== undefined
const isGeographicalFeature = (geoJson?: GeoJSON): geoJson is Feature => geoJson?.type === "Feature"

export default function PlansPage() {
    const { hasRole } = useAuth()
    const { t } = useTranslation()
    const { showCreatePlaceToast } = usePredefinedUserInput()
    const { formatKilometers } = useFormatters()
    const navigate = useAppNavigate()

    const [selectedTab, setSelectedTab] = useQueryParamState(TAB_URL_QUERY_PARAM_NAME, PlansMenuTabName.ConsideredPlaces)

    const { candidatePlaces, changeCurrentLocation, createCandidatePlace, removeCandidatePlace } = useCandidatePlaces({ include: [PlaceIncludedEntity.Categories] })
    const { places: visitedPlaces } = useTimeFilteredRegularPlaces({ include: [PlaceIncludedEntity.Categories], sort: PlaceSortingStrategy.Quality, maxEnd: getCurrentOrMaximumAllowedTimestamp() })
    const { trips, removeTrip } = useCandidateTrips()
    const { regions } = useRegions()
    const countryCategoriesMap = useCountryCategoriesMap()

    const [maxDistance, setMaxDistance] = useState(DEFAULT_MAX_DISTANCE)
    const [maxQuality, setMaxQuality] = useState(DEFAULT_MAX_QUALITY)

    const getRegionColor = useCallback((region: GeographicalRegion) => {
        const containsRegion = (place: Place) => place.categories?.some(category => category.id === region.category.id)

        const candidatePlacesInRegion = candidatePlaces?.filter(containsRegion) || []
        const visitedPlacesInRegion = visitedPlaces?.filter(containsRegion) || []

        const qualities = visitedPlacesInRegion.filter(place => place.quality).map(place => place.quality)
        const minimumQuality = Math.min(...qualities)
        const averageQuality = qualities.reduce((a, b) => a + b, 0) / qualities.length

        if (averageQuality > 0 && averageQuality < INSUFFICIENT_QUALITY_THRESHOLD) {
            return "#FF0000"
        }

        if ((averageQuality >= INSUFFICIENT_QUALITY_THRESHOLD && averageQuality < AVERAGE_QUALITY_THRESHOLD) || minimumQuality < AVERAGE_QUALITY_THRESHOLD) {
            return "#FFFF00"
        }

        if (candidatePlacesInRegion.length > 0) {
            return "#9ACD32"
        }

        return "#008000"
    }, [candidatePlaces, visitedPlaces])

    const groupPlacesByKey = (places: Place[] | undefined, getKey: (place: Place) => string | undefined): Record<string, Place[]> =>
        (places ?? []).reduce<Record<string, Place[]>>((acc, place) => {
            const key = getKey(place)
            return key ? { ...acc, [key]: [...(acc[key] ?? []), place] } : acc
        }, {})


    const regionGeojsonsWithMetadata = useMemo(() => {
        // TODO: Add the filter to the API endpoint.
        return regions?.filter(isGeographicalRegion)
            ?.filter(region => {
                const geoJson = region.geoJson as GeoJSON
                return isGeographicalFeature(geoJson) && geoJson.geometry?.type !== "Point"
            })
            ?.filter(region => region.category.category === CategoryCategory.Administrative)
            ?.filter(region => (visitedPlaces ?? []).some(place => place.categories?.some(c => c.id === region.category.id)))
            ?.map(region => ({
                ...region,
                geoJson: {
                    ...region.geoJson,
                    properties: {
                        ...(region.geoJson as Feature).properties,
                        id: region.category.id,
                        color: getRegionColor(region)
                    }
                }
            }))
    }, [regions, getRegionColor])

    const filteredCandidatePlaces = useMemo(() => candidatePlaces?.filter(place => !place.distance || place.distance <= maxDistance), [candidatePlaces, maxDistance])
    const filteredVisitedPlaces = useMemo(() => visitedPlaces?.filter(place => !place.quality || place.quality <= maxQuality), [visitedPlaces, maxQuality])

    const furthestPlace = useMemo(() => candidatePlaces?.filter(place => place.distance)?.reduce((max, place) => !max || place.distance > max.distance ? place : max, undefined), [candidatePlaces])
    const lowestQualityPlace = useMemo(() => visitedPlaces?.filter(place => place?.quality ?? 0)?.reduce((min, place) => !min || place.quality < min.quality ? place : min, undefined), [visitedPlaces])

    const countriesCandidatePlaces = useMemo(() => groupPlacesByKey(filteredCandidatePlaces, place => place.country), [filteredCandidatePlaces])
    const countriesVisitedPlaces = useMemo(() => groupPlacesByKey(filteredVisitedPlaces, place => place.country), [filteredVisitedPlaces])
    const regionsVisitedPlaces = useMemo(() => groupPlacesByKey(filteredVisitedPlaces, place => place.getCategory(CategoryCategory.Administrative)?.name), [filteredVisitedPlaces])

    const tabs = [
        {
            name: PlansMenuTabName.ConsideredPlaces,
            label: t("menu.tab.label.consideredPlaces"),
            enabled: hasRole(UserRole.PlaceRead)
        },
        {
            name: PlansMenuTabName.VisitedPlaces,
            label: t("menu.tab.label.visitedPlaces"),
            enabled: hasRole(UserRole.PlaceRead) && hasRole(UserRole.PortalFutureRead)
        },
        {
            name: PlansMenuTabName.VisitedRegions,
            label: t("menu.tab.label.visitedRegions"),
            enabled: hasRole(UserRole.RegionRead) && hasRole(UserRole.PortalFutureRead)
        },
        {
            name: PlansMenuTabName.ConsideredTrips,
            label: t("menu.tab.label.consideredTrips"),
            enabled: hasRole(UserRole.TripRead) && hasRole(UserRole.PortalFutureRead)
        }
    ]

    const activeTab = useMemo(() => tabs.find(tab => tab.name === selectedTab)?.name, [tabs, selectedTab])

    const handleCandidatePlaceCreated = () => {
        showCreatePlaceToast((name, address) => createCandidatePlace(name, address).then(place => (navigate(place), place)))
    }

    return tabs.some(label => label.enabled) && (
        <>
            <TabMenu
                tabs={tabs}
                selectedTab={selectedTab}
                onTabSelected={setSelectedTab} />
            {hasRole(UserRole.PlaceRead) && activeTab === PlansMenuTabName.ConsideredPlaces && (
                <>
                    <StaticMapFrame>
                        <PlaceMap
                            places={filteredCandidatePlaces}
                            placeMainCategorySelector={place => countryCategoriesMap.get(place.country)} />
                    </StaticMapFrame>
                    {furthestPlace && (
                        <Slider
                            name={t("plan.slider.maxDistance")}
                            valueFormatter={formatKilometers}
                            value={maxDistance}
                            defaultValue={DEFAULT_MAX_DISTANCE}
                            minValue={1}
                            maxValue={furthestPlace?.distance}
                            onValueChanged={setMaxDistance} />
                    )}
                    <CategoryCardGrid
                        rowSize={5}
                        categories={[...countryCategoriesMap.values()]}
                        categoriesPlaces={countriesCandidatePlaces}
                        onCurrentLocationChanged={changeCurrentLocation}
                        onMaximumDistanceChanged={setMaxDistance}
                        onPlaceRemoved={hasRole(UserRole.PlaceEdit) && removeCandidatePlace} />
                </>
            )}
            {hasRole(UserRole.PlaceRead) && hasRole(UserRole.PortalFutureRead) && activeTab === PlansMenuTabName.VisitedPlaces && (
                <>
                    <StaticMapFrame>
                        <PlaceMap
                            places={filteredVisitedPlaces}
                            placeMainCategorySelector={place => countryCategoriesMap.get(place.country)} />
                    </StaticMapFrame>
                    <Slider
                        name={t("plan.slider.maxQuality")}
                        valueFormatter={value => `${value}%`}
                        value={maxQuality}
                        defaultValue={DEFAULT_MAX_QUALITY}
                        minValue={Math.ceil(lowestQualityPlace?.quality ?? 0)}
                        maxValue={100}
                        onValueChanged={setMaxQuality} />
                    <CategoryCardGrid
                        rowSize={5}
                        categories={[...countryCategoriesMap.values()]}
                        categoriesPlaces={countriesVisitedPlaces} />
                </>
            )}
            {hasRole(UserRole.RegionRead) && hasRole(UserRole.PortalFutureRead) && activeTab === PlansMenuTabName.VisitedRegions && (
                <>
                    <StaticMapFrame>
                        <RegionMap
                            regions={regionGeojsonsWithMetadata}
                            onClick={geographicalRegion => Promise.resolve(navigate(geographicalRegion.category))} />
                    </StaticMapFrame>
                    <CategoryCardGrid
                        rowSize={5}
                        categories={regionGeojsonsWithMetadata?.map(region => region.category)}
                        categoriesPlaces={regionsVisitedPlaces} />
                </>
            )}
            {hasRole(UserRole.TripRead) && hasRole(UserRole.PortalFutureRead) && activeTab === PlansMenuTabName.ConsideredTrips && (
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
