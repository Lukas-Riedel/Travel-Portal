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

    const filteredTrips = trips?.flatMap(trip => trip.flights ?? [])?.filter(flight => flight.airline?.id === airlineId)
        ?.filter(flight => flight.end < getCurrentOrMaximumAllowedTimestamp())
    const flights = filteredTrips && [...filteredTrips].reverse()

    return hasRole(UserRole.AirlineRead) && (
        <>
            <PageHeader
                name={airline?.name ?? null}
                onNameChanged={hasRole(UserRole.AirlineEdit) ? updateAirlineName : undefined}
                onRemoved={hasRole(UserRole.AirlineEdit) ? removeAirline : undefined} />
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