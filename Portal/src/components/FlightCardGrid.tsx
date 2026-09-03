import type { Flight } from "../types/CoreSwaggerTypes.ts"
import CardGrid from "./CardGrid.tsx"
import FlightCard from "./FlightCard.tsx"

interface FlightCardGridProps {
    flights: Flight[] | null
    rowSize: number
    columnSize?: number
}

export default function FlightCardGrid({ flights, rowSize, columnSize }: FlightCardGridProps) {
    return (
        <CardGrid
            rowSize={rowSize}
            columnSize={columnSize}>
            {flights?.map(flight => (
                <FlightCard
                    key={flight.start}
                    flight={flight} />
            ))}
        </CardGrid>
    )
}