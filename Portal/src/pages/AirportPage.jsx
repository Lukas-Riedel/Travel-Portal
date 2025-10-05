import { useParams } from "react-router-dom"
import PageHeader from "../components/PageHeader"
import { useMemo } from "react"
import { useCategories } from "../hooks/useCategories"
import { useRegularTrips } from "../hooks/useRegularTrips"
import FlightCardGrid from "../components/FlightCardGrid"
import FlightMap from "../components/FlightMap"
import { useAirport } from "../hooks/useAirport"

export default function AirportPage() {
    const { airportId } = useParams()

    const trips = useRegularTrips({ include: "flights" })
    const countryCategories = useCategories({ categories: "country" })
    const { airport, updateAirportLongName } = useAirport(airportId)

    const flights = useMemo(() => {
        const filteredFlights = trips?.flatMap(trip => trip.flights ?? [])?.filter(flight => flight.registration)
            ?.filter(flight => flight.from.id === airportId || flight.to.id === airportId)
        return filteredFlights && [...filteredFlights].reverse()
    }, [trips])

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

    return (
        <>
            <PageHeader
                name={airport && (airport.longName ?? airport.code)}
                categories={airport ? [countryCategoriesMap.get(airport.country)] : []}
                onNameChanged={updateAirportLongName} />
            <div className="h-[400px] md:h-[700px] my-4">
                <FlightMap
                    flights={flights}
                    airportMainCategorySelector={airport => countryCategoriesMap.get(airport.country)} />
            </div>
            <FlightCardGrid flights={flights} />
        </>
    )
}