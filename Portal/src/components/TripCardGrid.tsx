import type { Trip } from "../types/CoreSwaggerTypes.ts"
import CardGrid from "./CardGrid.tsx"
import TripCard from "./TripCard.tsx"

interface TripCardGridProps {
    trips: Trip[] | null
    rowSize: number
    onTripRemoved?: (tripId: string) => Promise<void>
}

export default function TripCardGrid({ trips, rowSize, onTripRemoved }: TripCardGridProps) {
    return (
        <CardGrid rowSize={rowSize}>
            {trips?.map(trip => (
                <TripCard
                    key={trip.id}
                    trip={trip}
                    onTripRemoved={onTripRemoved} />
            ))}
        </CardGrid>
    )
}
