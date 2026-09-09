import type { Feature, GeoJSON } from "geojson"
import { Plus } from "lucide-react"
import { useCallback, useMemo, useState } from "react"
import { useTranslation } from "react-i18next"

import type { Place } from "../classes/Place.ts"
import CategoryCardGrid from "../components/CategoryCardGrid"
import FloatingButton from "../components/FloatingButton"
import PlaceMap from "../components/PlaceMap"
import RegionMap from "../components/RegionMap.jsx"
import Slider from "../components/Slider"
import StaticMapFrame from "../components/StaticMapFrame.tsx"
import TabMenu from "../components/TabMenu"
import TripCardGrid from "../components/TripCardGrid"
import { useAuth } from "../contexts/AuthContext"
import { useAppNavigate } from "../hooks/useAppNavigate.ts"
import { useCandidatePlaces } from "../hooks/useCandidatePlaces"
import { useCandidateTrips } from "../hooks/useCandidateTrips"
import { useCountryCategoriesMap } from "../hooks/useCountryCategoriesMap.ts"
import { useFormatters } from "../hooks/useFormatters.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { useQueryParamState } from "../hooks/useQueryParamState.ts"
import { useRegions } from "../hooks/useRegions.ts"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"
import { CategoryCategory, type CompositeRegion, type GeographicalRegion, PlaceIncludedEntity, PlaceSortingStrategy, UserRole } from "../types/CoreSwaggerTypes.ts"
import { PlansMenuTabName } from "../types/PlansMenuTabName.ts"
import { getCurrentOrMaximumAllowedTimestamp } from "../utils/timeUtils.ts"

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

        const qualities = visitedPlacesInRegion.filter(place => place.quality).map(place => place.quality as number)
        const minimumQuality = Math.min(...qualities)
        const averageQuality = qualities.reduce((a: number, b: number) => a + b, 0) / qualities.length

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

    // TODO: Add the filter to the API endpoint.
    const regionGeojsonsWithMetadata = useMemo(() => regions?.filter(isGeographicalRegion)
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
        })), [regions, getRegionColor, visitedPlaces])

    const filteredCandidatePlaces = candidatePlaces?.filter(place => !place.distance || place.distance <= maxDistance)
    const filteredVisitedPlaces = visitedPlaces?.filter(place => !place.quality || place.quality <= maxQuality)

    const furthestPlace = candidatePlaces?.filter((place): place is Place & { distance: number } => place.distance != null)?.reduce((max, place) => !max || place.distance > max.distance ? place : max, undefined as (Place & { distance: number }) | undefined)
    const lowestQualityPlace = visitedPlaces?.filter((place): place is Place & { quality: number } => place.quality != null)?.reduce((min, place) => !min || place.quality < min.quality ? place : min, undefined as (Place & { quality: number }) | undefined)

    const countriesCandidatePlaces = groupPlacesByKey(filteredCandidatePlaces, place => place.country)
    const countriesVisitedPlaces = groupPlacesByKey(filteredVisitedPlaces, place => place.country)
    const regionsVisitedPlaces = groupPlacesByKey(filteredVisitedPlaces, place => place.getCategory(CategoryCategory.Administrative)?.name)

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

    const activeTab = tabs.find(tab => tab.name === selectedTab)?.name

    const handleCandidatePlaceCreated = () => {
        showCreatePlaceToast((name, address) => createCandidatePlace(name, address).then(place => (navigate(place), place)))
    }

    return tabs.some(label => label.enabled) && (
        <>
            <TabMenu
                tabs={tabs}
                selectedTab={selectedTab ?? undefined}
                onTabSelected={setSelectedTab} />
            {hasRole(UserRole.PlaceRead) && activeTab === PlansMenuTabName.ConsideredPlaces && (
                <>
                    <StaticMapFrame>
                        <PlaceMap
                            places={filteredCandidatePlaces ?? null}
                            placeMainCategorySelector={place => countryCategoriesMap?.get(place.country ?? "") ?? null} />
                    </StaticMapFrame>
                    {furthestPlace && (
                        <Slider
                            name={t("plan.slider.maxDistance")}
                            valueFormatter={formatKilometers}
                            value={maxDistance}
                            defaultValue={DEFAULT_MAX_DISTANCE}
                            minValue={1}
                            maxValue={furthestPlace.distance}
                            onValueChanged={setMaxDistance} />
                    )}
                    <CategoryCardGrid
                        rowSize={5}
                        categories={[...(countryCategoriesMap ?? new Map()).values()]}
                        categoriesPlaces={countriesCandidatePlaces}
                        onCurrentLocationChanged={changeCurrentLocation}
                        onMaximumDistanceChanged={setMaxDistance}
                        onPlaceRemoved={hasRole(UserRole.PlaceEdit) ? removeCandidatePlace : undefined} />
                </>
            )}
            {hasRole(UserRole.PlaceRead) && hasRole(UserRole.PortalFutureRead) && activeTab === PlansMenuTabName.VisitedPlaces && (
                <>
                    <StaticMapFrame>
                        <PlaceMap
                            places={filteredVisitedPlaces ?? null}
                            placeMainCategorySelector={place => countryCategoriesMap?.get(place.country ?? "") ?? null} />
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
                        categories={[...(countryCategoriesMap ?? new Map()).values()]}
                        categoriesPlaces={countriesVisitedPlaces} />
                </>
            )}
            {hasRole(UserRole.RegionRead) && hasRole(UserRole.PortalFutureRead) && activeTab === PlansMenuTabName.VisitedRegions && (
                <>
                    <StaticMapFrame>
                        <RegionMap
                            regions={regionGeojsonsWithMetadata ?? null}
                            onClick={geographicalRegion => geographicalRegion != null ? Promise.resolve(navigate(geographicalRegion.category)) : Promise.resolve()} />
                    </StaticMapFrame>
                    <CategoryCardGrid
                        rowSize={5}
                        categories={regionGeojsonsWithMetadata?.map(region => region.category) ?? null}
                        categoriesPlaces={regionsVisitedPlaces} />
                </>
            )}
            {hasRole(UserRole.TripRead) && hasRole(UserRole.PortalFutureRead) && activeTab === PlansMenuTabName.ConsideredTrips && (
                <TripCardGrid
                    rowSize={3}
                    trips={trips ?? null}
                    onTripRemoved={hasRole(UserRole.TripEdit) ? removeTrip : undefined} />
            )}
            {hasRole(UserRole.PlaceEdit) && (
                <FloatingButton
                    icon={Plus}
                    onClick={handleCandidatePlaceCreated} />
            )}
        </>
    )
}
