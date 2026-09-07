import { useMemo } from "react"
import { useParams } from "react-router-dom"

import FlightCardGrid from "../components/FlightCardGrid.tsx"
import FlightMap from "../components/FlightMap.tsx"
import PageHeader from "../components/PageHeader.tsx"
import StaticMapFrame from "../components/StaticMapFrame.tsx"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useAirline } from "../hooks/useAirline.ts"
import { useCountryCategoriesMap } from "../hooks/useCountryCategoriesMap.ts"
import { useRegularTrips } from "../hooks/useRegularTrips.ts"
import { TripIncludedEntity, UserRole } from "../types/CoreSwaggerTypes.ts"
import { getCurrentOrMaximumAllowedTimestamp } from "../utils/timeUtils.ts"

export default function AirlinePage() {
    const { airlineId } = useParams()
    const { hasRole } = useAuth()

    const { airline, updateAirlineName, removeAirline } = useAirline(airlineId)
    const { trips } = useRegularTrips({ include: [TripIncludedEntity.Flights] })
    const countryCategoriesMap = useCountryCategoriesMap()

    const flights = useMemo(() => {
        const filteredTrips = trips?.flatMap(trip => trip.flights ?? [])?.filter(flight => flight.airline?.id === airlineId)
            ?.filter(flight => flight.end < getCurrentOrMaximumAllowedTimestamp())
        return filteredTrips && [...filteredTrips].reverse()
    }, [trips, airlineId])


    return hasRole(UserRole.AirlineRead) && (
        <>
            <PageHeader
                name={airline?.name}
                onNameChanged={hasRole(UserRole.AirlineEdit) && updateAirlineName}
                onRemoved={hasRole(UserRole.AirlineEdit) && removeAirline} />
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