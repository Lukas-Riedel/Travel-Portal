import type { TripIdentifier } from "../types/CoreSwaggerTypes"
import { getTripFullName } from "../utils/tripUtils"
import Bar from "./Bar"
import BarItem from "./BarItem"

interface TripBarProps {
    trips: TripIdentifier[] | null
}

export default function TripBar({ trips }: TripBarProps) {
    return (!trips || trips.length > 0) && (
        <Bar>
            {trips && [...trips].reverse().map(trip => (
                <BarItem
                    key={trip.id}
                    to={trip}>
                    {getTripFullName(trip)}
                </BarItem>
            ))}
        </Bar>
    )
}