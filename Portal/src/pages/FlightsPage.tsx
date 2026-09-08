import { useMemo } from "react"

import FlightCardGrid from "../components/FlightCardGrid"
import FlightMap from "../components/FlightMap"
import StaticMapFrame from "../components/StaticMapFrame.tsx"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useCountryCategoriesMap } from "../hooks/useCountryCategoriesMap.ts"
import { useRegularTrips } from "../hooks/useRegularTrips"
import { TripIncludedEntity, UserRole } from "../types/CoreSwaggerTypes.ts"
import { getCurrentOrMaximumAllowedTimestamp } from "../utils/timeUtils.ts"

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
            <StaticMapFrame>
                <FlightMap
                    flights={flights ?? null}
                    airportMainCategorySelector={airport => countryCategoriesMap?.get(airport.country ?? "") ?? null} />
            </StaticMapFrame>
            <FlightCardGrid
                rowSize={4}
                columnSize={6}
                flights={flights ?? null} />
        </>
    )
}
