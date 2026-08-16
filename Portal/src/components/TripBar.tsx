import { TailSpin } from "react-loader-spinner"
import { Link } from "react-router-dom"
import type { Trip } from "../classes/Trip"
import BarItem from "./BarItem"
import Bar from "./Bar"
import type { TripIdentifier } from "../types/CoreSwaggerTypes"
import { getTripFullName } from "../utils/formattingUtils"

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