import { useParams } from "react-router-dom"
import PageHeader from "../components/PageHeader"
import { useMemo } from "react"
import { useCategories } from "../hooks/useCategories"
import { useRegularTrips } from "../hooks/useRegularTrips"
import FlightCardGrid from "../components/FlightCardGrid"
import FlightMap from "../components/FlightMap"
import { useAirline } from "../hooks/useAirline"

export default function AirlinePage() {
    const { airlineId } = useParams()

    const trips = useRegularTrips({ include: ["flights"] })
    const countryCategories = useCategories({ categories: ["country"] })
    const { airline, updateAirlineName, removeAirline } = useAirline(airlineId)

    const flights = useMemo(() => {
        const filteredTrips = trips?.flatMap(trip => trip.flights ?? [])?.filter(flight => flight.airline?.id === airline?.id)
        return filteredTrips && [...filteredTrips].reverse()
    }, [trips])

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

    return (
        <>
            <PageHeader
                name={airline?.name}
                onNameChanged={updateAirlineName}
                onRemoved={removeAirline} />
            <div className="h-[400px] md:h-[700px] my-4">
                <FlightMap
                    flights={flights}
                    airportMainCategorySelector={airport => countryCategoriesMap.get(airport.country)} />
            </div>
            <FlightCardGrid flights={flights} />
        </>
    )
}