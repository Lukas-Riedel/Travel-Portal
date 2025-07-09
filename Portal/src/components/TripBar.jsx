import { TailSpin } from "react-loader-spinner"
import { Link } from "react-router-dom"

const loadingTripsCount = 2

export default function TripBar({ trips }) {
    return (!trips || trips.length > 0) && (
        <div className="flex flex-col lg:flex-row justify-center gap-3 px-4 my-4">
            {trips ? [...trips].reverse().map(trip => (
                <Link
                    key={trip.d}
                    className="flex w-full lg:w-auto text-center items-center justify-center px-4 py-2 bg-white rounded-lg shadow text-sm font-medium hover:bg-gray-100 transition"
                    to={`/trip/${trip.id}`}>
                    {trip.name + " " + trip.year}
                </Link>
            )) : Array.from({ length: loadingTripsCount }).map((_, index) => (
                <div
                    key={index}
                    className="flex w-full lg:w-auto text-center items-center justify-center px-4 py-2 bg-white rounded-lg shadow text-sm font-medium hover:bg-gray-100 transition">
                    <div className="mx-4 min-w-[36px] min-h-[24px] flex items-center justify-center">
                        <TailSpin
                            color="black"
                            height={16}
                            width={16} />
                    </div>
                </div>
            ))}
        </div>
    )
}