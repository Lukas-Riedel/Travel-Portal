import type { Flight } from "../types/CoreSwaggerTypes.ts"
import CardGrid from "./CardGrid.tsx"
import FlightCard from "./FlightCard.tsx"

interface FlightCardGridProps {
    flights: Flight[] | null
    rowSize: number
}

export default function FlightCardGrid({ flights, rowSize }: FlightCardGridProps) {
    return (
        <CardGrid rowSize={rowSize}>
            {flights?.map(flight => (
                <FlightCard
                    key={flight.start}
                    flight={flight} />
            ))}
        </CardGrid>
    )
}