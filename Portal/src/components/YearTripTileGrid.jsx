import { Link } from "react-router-dom"
import TripTileGrid from "./TripTileGrid.jsx"
import { useMemo } from "react"
import { useAuth } from "../contexts/AuthContext.jsx"
import { getSortedTrips } from "../utils/helpers"

export default function YearTripTileGrid({ year, trips }) {
    const { isAdmin } = useAuth()

    const yearTrips = useMemo(() => trips?.filter(trip => trip.year == year && (trip.isPast() || trip.isDayTrips())), [trips, year])

    return (
        <div className="my-4">
            <div className="flex justify-center mb-2">
                <Link
                    className="hover:underline text-2xl font-bold"
                    to={`/year/${year}`}>
                    {year}
                </Link>
            </div>
            <TripTileGrid trips={getSortedTrips(yearTrips, isAdmin)} />
        </div>
    )
}