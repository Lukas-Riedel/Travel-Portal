import { useParams } from "react-router-dom"
import PageHeader from "../components/PageHeader"
import { useMemo } from "react"
import { useCategories } from "../hooks/useCategories"
import { useRegularTrips } from "../hooks/useRegularTrips"
import FlightCardGrid from "../components/FlightCardGrid"
import FlightMap from "../components/FlightMap"
import { useAirline } from "../hooks/useAirline"
import { useAuth } from "../contexts/AuthContext.tsx"
import { UserRole } from "../types/CoreSwaggerTypes.ts"
import { getCurrentOrMaximumAllowedTimestamp } from "../utils/timeUtils.ts"

export default function AirlinePage() {
    const { airlineId } = useParams()
    const { hasRole } = useAuth()

    const trips = useRegularTrips({ include: ["flights"] })
    const countryCategories = useCategories({ categories: ["country"] })
    const { airline, updateAirlineName, removeAirline } = useAirline(airlineId)

    const flights = useMemo(() => {
        const filteredTrips = trips?.flatMap(trip => trip.flights ?? [])?.filter(flight => flight.airline?.id === airline?.id)
            ?.filter(flight => flight.end < getCurrentOrMaximumAllowedTimestamp())
        return filteredTrips && [...filteredTrips].reverse()
    }, [trips])

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

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
                flights={flights} />
        </>
    )
}