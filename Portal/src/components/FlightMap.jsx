import { useMemo } from "react"
import Map from "./Map.jsx"

export default function FlightMap({ flights, airportMainCategorySelector }) {
    const getColorByFlightCount = (count, maxCount) => {
        const colors = [
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

        const steps = Math.min(10, maxCount)
        const intervalSize = maxCount / steps
        const index = Math.min(Math.floor((count - 1) / intervalSize), steps - 1)
        return colors[index]
    }

    const getOpacityByFlightCount = (count, maxCount) => {
        const ratio = count / maxCount
        const adjustedRatio = Math.pow(ratio, Math.log(0.2) / Math.log(1 / maxCount))
        return 0.2 + (1 - 0.2) * adjustedRatio
    }

    const airports = useMemo(() => [...(flights?.map(f => f.from) || []), ...(flights?.map(f => f.to) || [])]
        .filter((airport, index, self) => airport && self.findIndex(a => a.id === airport.id) === index), [flights])
    const flightPaths = useMemo(() => flights?.reduce((acc, flight) => {
        const flightPath = acc.find(fp => (fp.from.id === flight.from.id && fp.to.id === flight.to.id)
            || (fp.to.id === flight.from.id && fp.from.id === flight.to.id))
        if (flightPath) {
            flightPath.count += 1
        }
        else {
            acc.push({
                from: {
                    id: flight.from.id,
                    latitude: flight.from.latitude,
                    longitude: flight.from.longitude
                },
                to: {
                    id: flight.to.id,
                    latitude: flight.to.latitude,
                    longitude: flight.to.longitude
                },
                count: 1
            })
        }
        return acc
    }, []), [flights])

    const maxFlightsPerFlightPathCount = useMemo(() => Math.max(...(flightPaths ?? []).map(fp => fp.count)), [flightPaths])

    return (
        <Map
            points={airports.map(airport => {
                return {
                    name: airport.name + " (" + airport.code + ")",
                    latitude: airport.latitude,
                    longitude: airport.longitude,
                    color: airportMainCategorySelector(airport)?.metadata?.color
                }
            })}
            lines={flightPaths?.map(fp => ({
                ...fp,
                color: getColorByFlightCount(fp.count, maxFlightsPerFlightPathCount),
                opacity: getOpacityByFlightCount(fp.count, maxFlightsPerFlightPathCount)
            }))} />
    )
}