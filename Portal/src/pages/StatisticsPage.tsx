import { useMemo } from "react"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useStatistics } from "../hooks/useStatistics"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"
import { useRegularTrips } from "../hooks/useRegularTrips.ts"
import { useCategories } from "../hooks/useCategories"
import { useYears } from "../hooks/useYears.ts"
import StaticMapFrame from "../components/StaticMapFrame.tsx"
import PlaceMapAndFlightMapToggle from "../components/PlaceMapAndFlightMapToggle"
import StatisticsCardGrid from "../components/StatisticsCardGrid"
import { CategoryCategory, PlaceSortingStrategy, TripIncludedEntity, UserRole, YearIncludedEntity } from "../types/CoreSwaggerTypes.ts"
import { useCountryCategoriesMap } from "../hooks/useCountryCategoriesMap.ts"

export default function StatisticsPage() {
    const { hasRole } = useAuth()

    const statistics = useStatistics()
    const years = useYears({ include: [YearIncludedEntity.Statistics] })
    const { places } = useTimeFilteredRegularPlaces({ sort: PlaceSortingStrategy.ValueScore })
    const { trips } = useRegularTrips({ include: [TripIncludedEntity.Flights] })
    const countryCategoriesMap = useCountryCategoriesMap()

    const flights = useMemo(() => (trips ?? []).flatMap(trip => trip.flights).filter(Boolean).filter(flight => flight.registration), [trips])

    return hasRole(UserRole.StatisticsRead) && (
        <>
            <StaticMapFrame>
                <PlaceMapAndFlightMapToggle
                    places={places}
                    flights={flights}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)}
                    airportMainCategorySelector={airport => countryCategoriesMap.get(airport.country)} />
            </StaticMapFrame>
            <StatisticsCardGrid
                rowSize={2}
                statistics={statistics}
                years={years} />
        </>
    )
}
