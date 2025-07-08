import { useParams } from "react-router-dom"
import PageHeader from "../components/PageHeader"
import { useMemo } from "react"
import { useCategories } from "../hooks/useCategories"
import { useRegularTrips } from "../hooks/useRegularTrips"
import FlightCardGrid from "../components/FlightCardGrid"
import FlightMap from "../components/FlightMap"
import { useAirlines } from "../hooks/useAirlines"
import { getAirlineCodeForFlight } from "../utils/helpers"

export default function AirlinePage() {
    const { airlineName } = useParams()

    const trips = useRegularTrips({ include: "FLIGHTS" })
    const countryCategories = useCategories({ categories: "COUNTRY" })
    const airlines = useAirlines()

    const relevantAirlines = useMemo(() => airlines?.filter(airline => airline.name === airlineName), [airlines, airlineName])
    const flights = useMemo(() => [...(trips?.flatMap(trip => trip.flights)?.filter(flight => flight.registration)
        ?.filter(flight => relevantAirlines?.some(airline => getAirlineCodeForFlight(flight.flight) === airline.code)) ?? [])].reverse(), [trips])

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

    return (
        <>
            <PageHeader name={airlineName} />
            <div className="h-[400px] md:h-[700px] my-4">
                <FlightMap
                    flights={flights}
                    airportMainCategorySelector={airport => countryCategoriesMap.get(airport.country)} />
            </div>
            <FlightCardGrid flights={flights} />
        </>
    )
}