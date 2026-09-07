import { useMemo } from "react"
import { useParams } from "react-router-dom"

import FlightCardGrid from "../components/FlightCardGrid.tsx"
import FlightMap from "../components/FlightMap.tsx"
import PageHeader from "../components/PageHeader.tsx"
import StaticMapFrame from "../components/StaticMapFrame.tsx"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useAirport } from "../hooks/useAirport.ts"
import { useCountryCategoriesMap } from "../hooks/useCountryCategoriesMap.ts"
import { useRegularTrips } from "../hooks/useRegularTrips.ts"
import { TripIncludedEntity, UserRole } from "../types/CoreSwaggerTypes.ts"
import { getCurrentOrMaximumAllowedTimestamp } from "../utils/timeUtils.ts"

export default function AirportPage() {
    const { airportId } = useParams()
    const { hasRole } = useAuth()

    const { airport, updateAirportLongName } = useAirport(airportId)
    const { trips } = useRegularTrips({ include: [TripIncludedEntity.Flights] })
    const countryCategoriesMap = useCountryCategoriesMap()

    const flights = useMemo(() => {
        const filteredFlights = trips?.flatMap(trip => trip.flights ?? [])
            ?.filter(flight => flight.registration)
            ?.filter(flight => flight.end < getCurrentOrMaximumAllowedTimestamp())
            ?.filter(flight => flight.from.id === airportId || flight.to.id === airportId)
        return filteredFlights && [...filteredFlights].reverse()
    }, [trips, airportId])

    return hasRole(UserRole.AirportRead) && (
        <>
            <PageHeader
                name={airport && (airport.longName ?? airport.code)}
                categories={airport?.country ? [countryCategoriesMap.get(airport.country)] : []}
                onNameChanged={hasRole(UserRole.AirportEdit) && updateAirportLongName} />
            <StaticMapFrame>
                <FlightMap
                    flights={flights}
                    airportMainCategorySelector={airport => countryCategoriesMap.get(airport.country)} />
            </StaticMapFrame>
            <FlightCardGrid
                rowSize={4}
                columnSize={6}
                flights={flights} />
        </>
    )
}
