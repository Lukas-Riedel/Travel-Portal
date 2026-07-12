import { Link } from "react-router-dom"
import TripTileGrid from "./TripTileGrid"
import { useMemo } from "react"
import type { Trip } from "../classes/Trip"
import AppLink from "./AppLink"

interface YearTripTileGridProps {
    year: number
    trips: Trip[] | null
}

export default function YearTripTileGrid({ year, trips }: YearTripTileGridProps) {
    // TODO: This should be ensured by the caller.
    const yearTrips = useMemo(() => trips?.filter(trip => trip.year == year && trip.isPast()), [trips, year])

    return yearTrips?.length > 0 && (
        <div className="my-4">
            <div className="flex justify-center mb-2">
                <AppLink
                    className="hover:underline text-2xl font-bold"
                    to={year}>
                    {year}
                </AppLink>
            </div>
            <TripTileGrid trips={yearTrips.slice().reverse()} />
        </div>
    )
}