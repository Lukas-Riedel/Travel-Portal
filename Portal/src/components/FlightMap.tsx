import { useMemo } from "react"
import Map from "./Map"
import type { Airport, Category, Flight } from "../types/CoreSwaggerTypes"
import type { FlightPath } from "../types/FlightPath"
import { useAppNavigate } from "../hooks/useAppNavigate"

const PATH_COLORS = [
    "#b0b0b0",
    "#ffa500",
    "#ff8c00",
    "#ff4500",
    "#ff0000",
    "#8b0000",
    "#800080",
    "#4b0082",
    "#00008b",
    "#0000ff"
]

interface FlightMapProps {
    flights: Flight[] | null
    airportMainCategorySelector: (airport: Airport) => Category | null
}

export default function FlightMap({ flights, airportMainCategorySelector }: FlightMapProps) {
    const navigate = useAppNavigate()

    const getColorByFlightCount = (count: number, maxCount: number) => {
        const steps = Math.min(PATH_COLORS.length, maxCount)
        const intervalSize = maxCount / steps
        const index = Math.min(Math.floor((count - 1) / intervalSize), steps - 1)
        return PATH_COLORS[index]
    }

    const airports = useMemo(() => [...(flights?.map(f => f.from) ?? []), ...(flights?.map(f => f.to) ?? [])]
        .filter((airport, index, self) => self.findIndex(a => a.id === airport.id) === index), [flights])

    const flightPaths = useMemo<FlightPath[]>(() => flights?.reduce((flightPaths: FlightPath[], flight) => {
        const flightPath = flightPaths.find(flightPath => (flightPath.from.id === flight.from.id && flightPath.to.id === flight.to.id)
            || (flightPath.to.id === flight.from.id && flightPath.from.id === flight.to.id))

        if (flightPath) {
            flightPath.count += 1
        }
        else {
            flightPaths.push({
                from: flight.from,
                to: flight.to,
                count: 1
            })
        }

        return flightPaths
    }, []) ?? [], [flights])

    const maxFlightsPerFlightPathCount = useMemo(() => Math.max(...(flightPaths ?? []).map(flightPath => flightPath.count)), [flightPaths])

    return (
        <Map
            points={airports.map(airport => ({
                name: airport.longName ?? airport.code,
                latitude: airport.latitude,
                longitude: airport.longitude,
                color: airportMainCategorySelector(airport)?.metadata?.color,
                unicode: airportMainCategorySelector(airport)?.metadata?.unicode,
                onClick: () => Promise.resolve(navigate(airport))
            }))}
            lines={flightPaths.map(fp => ({
                from: {
                    latitude: fp.from.latitude,
                    longitude: fp.from.longitude
                },
                to: {
                    latitude: fp.to.latitude,
                    longitude: fp.to.longitude
                },
                color: getColorByFlightCount(fp.count, maxFlightsPerFlightPathCount)
            }))} />
    )
}