import { useMemo } from "react"
import MapFrame from "../components/MapFrame.tsx"
import { useCategories } from "../hooks/useCategories"
import FlightMap from "../components/FlightMap"
import { useRegularTrips } from "../hooks/useRegularTrips"
import FlightCardGrid from "../components/FlightCardGrid"
import { useAuth } from "../contexts/AuthContext.tsx"
import { CategoryCategory, TripIncludedEntity, UserRole } from "../types/CoreSwaggerTypes.ts"
import { getCurrentOrMaximumAllowedTimestamp } from "../utils/timeUtils.ts"
import { useCountryCategoriesMap } from "../hooks/useCountryCategoriesMap.ts"

export default function FlightsPage() {
    const { hasRole } = useAuth()

    const { trips } = useRegularTrips({ include: [TripIncludedEntity.Flights] })

    const flights = useMemo(() => {
        const filteredFlights = trips?.flatMap(trip => trip.flights ?? [])?.filter(flight => flight.registration)
            ?.filter(flight => flight.end < getCurrentOrMaximumAllowedTimestamp())
        return filteredFlights && [...filteredFlights].reverse()
    }, [trips])

    const countryCategoriesMap = useCountryCategoriesMap()

    return hasRole(UserRole.TripFlightRead) && (
        <>
            <MapFrame>
                <FlightMap
                    flights={flights}
                    airportMainCategorySelector={airport => countryCategoriesMap.get(airport.country)} />
            </MapFrame>
            <FlightCardGrid
                rowSize={4}
                columnSize={6}
                flights={flights} />
        </>
    )
}
