import { Link } from "react-router-dom"
import TripTileGrid from "./TripTileGrid.jsx"
import { useMemo } from "react"
import { useAuth } from "../contexts/AuthContext.jsx"

export default function YearTripTileGrid({ year, trips }) {
    const yearTrips = useMemo(() => trips?.filter(trip => trip.year == year && trip.isPast()), [trips, year])

    return (
        <div className="my-4">
            <div className="flex justify-center mb-2">
                <Link
                    className="hover:underline text-2xl font-bold"
                    to={`/year/${year}`}>
                    {year}
                </Link>
            </div>
            <TripTileGrid trips={yearTrips?.slice()?.reverse()} />
        </div>
    )
}