import { useParams } from "react-router-dom"
import PageHeader from "../components/PageHeader.tsx"
import { useMemo } from "react"
import { useCategories } from "../hooks/useCategories.ts"
import { useRegularTrips } from "../hooks/useRegularTrips.ts"
import FlightCardGrid from "../components/FlightCardGrid.tsx"
import FlightMap from "../components/FlightMap.tsx"
import { useAirline } from "../hooks/useAirline.ts"
import { useAuth } from "../contexts/AuthContext.tsx"
import { TripIncludedEntity, UserRole } from "../types/CoreSwaggerTypes.ts"
import { getCurrentOrMaximumAllowedTimestamp } from "../utils/timeUtils.ts"
import { useCountryCategoriesMap } from "../hooks/useCountryCategoriesMap.ts"

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
            <div className="h-[400px] md:h-[700px] my-4">
                <FlightMap
                    flights={flights}
                    airportMainCategorySelector={airport => countryCategoriesMap.get(airport.country)} />
            </div>
            <FlightCardGrid
                rowSize={4}
                columnSize={6}
                flights={flights} />
        </>
    )
}