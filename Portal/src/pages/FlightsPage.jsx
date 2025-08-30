import { useMemo } from "react"
import { useCategories } from "../hooks/useCategories"
import FlightMap from "../components/FlightMap"
import { useRegularTrips } from "../hooks/useRegularTrips"
import { useStatistics } from "../hooks/useStatistics"
import StatisticsPanel from "../components/StatisticsPanel"
import FlightCardGrid from "../components/FlightCardGrid"

export default function FlightsPage() {
    const trips = useRegularTrips({ include: "flights" })
    const countryCategories = useCategories({ categories: "country" })
    const statistics = useStatistics()

    const flights = useMemo(() => {
        const filteredFlights = trips?.flatMap(trip => trip.flights ?? [])?.filter(flight => flight.registration);
        return filteredFlights && [...filteredFlights].reverse()
    }, [trips])

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

    return (
        <>
            <div className="h-[400px] md:h-[700px] my-4">
                <FlightMap
                    flights={flights}
                    airportMainCategorySelector={airport => countryCategoriesMap.get(airport.country)} />
            </div>
            <StatisticsPanel statistics={statistics} />
            <FlightCardGrid flights={flights} />
        </>
    )
}