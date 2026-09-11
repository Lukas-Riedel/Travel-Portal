
import PlaceMapAndFlightMapToggle from "../components/PlaceMapAndFlightMapToggle"
import StaticMapFrame from "../components/StaticMapFrame.tsx"
import StatisticsCardGrid from "../components/StatisticsCardGrid"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useCountryCategoriesMap } from "../hooks/useCountryCategoriesMap.ts"
import { useRegularTrips } from "../hooks/useRegularTrips.ts"
import { useStatistics } from "../hooks/useStatistics"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"
import { useYears } from "../hooks/useYears.ts"
import { PlaceSortingStrategy, TripIncludedEntity, UserRole, YearIncludedEntity } from "../types/CoreSwaggerTypes.ts"

export default function StatisticsPage() {
    const { hasRole } = useAuth()

    const statistics = useStatistics()
    const years = useYears({ include: [YearIncludedEntity.Statistics] })
    const { places } = useTimeFilteredRegularPlaces({ sort: PlaceSortingStrategy.ValueScore })
    const { trips } = useRegularTrips({ include: [TripIncludedEntity.Flights] })
    const countryCategoriesMap = useCountryCategoriesMap()

    const flights = (trips ?? []).flatMap(trip => trip.flights ?? []).filter(flight => flight.registration)

    return hasRole(UserRole.StatisticsRead) && (
        <>
            <StaticMapFrame>
                <PlaceMapAndFlightMapToggle
                    places={places}
                    flights={flights ?? null}
                    placeMainCategorySelector={place => countryCategoriesMap?.get(place.country ?? "") ?? null}
                    airportMainCategorySelector={airport => countryCategoriesMap?.get(airport.country ?? "") ?? null} />
            </StaticMapFrame>
            <StatisticsCardGrid
                rowSize={2}
                statistics={statistics}
                years={years} />
        </>
    )
}
