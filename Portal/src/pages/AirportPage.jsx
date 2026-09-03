import { useParams } from "react-router-dom"
import PageHeader from "../components/PageHeader"
import { useMemo } from "react"
import { useCategories } from "../hooks/useCategories"
import { useRegularTrips } from "../hooks/useRegularTrips"
import FlightCardGrid from "../components/FlightCardGrid"
import FlightMap from "../components/FlightMap"
import { useAirport } from "../hooks/useAirport"
import { useAuth } from "../contexts/AuthContext.tsx"
import { UserRole } from "../types/CoreSwaggerTypes.ts"
import { getCurrentOrMaximumAllowedTimestamp } from "../utils/timeUtils.ts"

export default function AirportPage() {
    const { airportId } = useParams()
    const { hasRole } = useAuth()

    const { trips } = useRegularTrips({ include: ["flights"] })
    const countryCategories = useCategories({ categories: ["country"] })
    const { airport, updateAirportLongName } = useAirport(airportId)

    const flights = useMemo(() => {
        const filteredFlights = trips?.flatMap(trip => trip.flights ?? [])?.filter(flight => flight.registration)
            ?.filter(flight => flight.from.id === airportId || flight.to.id === airportId)
            ?.filter(flight => flight.end < getCurrentOrMaximumAllowedTimestamp())
        return filteredFlights && [...filteredFlights].reverse()
    }, [trips])

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

    return hasRole(UserRole.AirportRead) && (
        <>
            <PageHeader
                name={airport && (airport.longName ?? airport.code)}
                categories={airport?.country ? [countryCategoriesMap.get(airport.country)] : []}
                onNameChanged={hasRole(UserRole.AirportEdit) && updateAirportLongName} />
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
