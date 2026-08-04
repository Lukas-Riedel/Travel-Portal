import { useMemo } from "react";
import PlaceMapAndFlightMapToggle from "../components/PlaceMapAndFlightMapToggle";
import StatisticsCardGrid from "../components/StatisticsCardGrid";
import { useStatistics } from "../hooks/useStatistics";
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces";
import { useCategories } from "../hooks/useCategories";
import { useYears } from "../hooks/useYears.ts";
import { UserRole } from "../types/CoreSwaggerTypes.ts";
import { useAuth } from "../contexts/AuthContext.tsx";
import { useRegularTrips } from "../hooks/useRegularTrips.ts";

export default function StatisticsPage() {
    const { hasRole } = useAuth()

    const statistics = useStatistics()
    const years = useYears({ include: ["statistics"] })
    const { places } = useTimeFilteredRegularPlaces({ sort: "-score" })
    const { trips } = useRegularTrips({ include: ["flights"] })
    const countryCategories = useCategories({ categories: ["country"] })

    const flights = useMemo(() => (trips ?? []).flatMap(trip => trip.flights).filter(Boolean).filter(flight => flight.registration), [trips])
    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

    return hasRole(UserRole.StatisticsRead) && (
        <>
            <div className="h-[400px] md:h-[700px] my-4">
                <PlaceMapAndFlightMapToggle
                    places={places}
                    flights={flights}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)}
                    airportMainCategorySelector={airport => countryCategoriesMap.get(airport.country)} />
            </div>
            <StatisticsCardGrid
                rowSize={2}
                statistics={statistics}
                years={years} />
        </>
    )
}