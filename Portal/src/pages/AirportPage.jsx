import { useParams } from "react-router-dom"
import PageHeader from "../components/PageHeader"
import { useMemo } from "react"
import { useCategories } from "../hooks/useCategories"
import { useRegularTrips } from "../hooks/useRegularTrips"
import FlightCardGrid from "../components/FlightCardGrid"
import FlightMap from "../components/FlightMap"

export default function AirportPage() {
    const { airportId } = useParams()

    const trips = useRegularTrips({ include: "FLIGHTS" })
    const countryCategories = useCategories({ categories: "COUNTRY" })

    const flights = useMemo(() => [...(trips?.flatMap(trip => trip.flights)?.filter(flight => flight.registration)
        ?.filter(flight => flight.from.id === airportId || flight.to.id === airportId) ?? [])].reverse(), [trips])

    // TODO: Introduce an API endpoint and fetch from it here.
    const airport = useMemo(() => {
        const flight = flights.find(f => f.from.id === airportId || f.to.id === airportId)
        return flight ? (flight.from.id === airportId ? flight.from : flight.to) : undefined
    }, [flights, airportId])

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

    return (
        <>
            <PageHeader
                name={airport && airport.code}
                categories={airport ? [countryCategoriesMap.get(airport.country)] : []} />
            <div className="h-[400px] md:h-[700px] my-4">
                <FlightMap
                    flights={flights}
                    airportMainCategorySelector={airport => countryCategoriesMap.get(airport.country)} />
            </div>
            <FlightCardGrid flights={flights} />
        </>
    )
}