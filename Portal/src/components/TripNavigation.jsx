import { useMemo } from "react"
import { useRegularTrips } from "../hooks/useRegularTrips"
import { Link } from "react-router-dom"
import { MoveLeft, MoveRight } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import { TailSpin } from "react-loader-spinner"

export default function TripNavigation({ trip }) {
    const { isAdmin } = useAuth()

    const trips = useRegularTrips()

    const previousTrip = useMemo(() => !trip?.isCandidate() && trips?.filter(t => t?.start < trip?.start).at(-1), [trip, trips])
    const nextTrip = useMemo(() => !trip?.isCandidate() && trips?.filter(t => t?.start > trip?.start)[0], [trip, trips])

    return (
        <div className="flex flex-col lg:flex-row lg:justify-between p-6 my-4 space-y-4 lg:space-y-0">
            {trip && trips ? (
                <>
                    {previousTrip && (previousTrip.start < Date.now() / 1000 || isAdmin) && (
                        <div>
                            <Link
                                className="flex w-full text-center items-center justify-center px-4 py-2 bg-white rounded-lg shadow text-sm font-medium hover:bg-gray-100 transition"
                                to={`/trip/${previousTrip.id}`}>
                                <MoveLeft
                                    className="mr-2"
                                    size={16} />
                                {previousTrip.getFullName()}
                            </Link>
                        </div>)}
                </>
            ) : (
                <div
                    className="flex w-full lg:w-auto text-center items-center justify-center px-4 py-2 bg-white rounded-lg shadow text-sm font-medium hover:bg-gray-100 transition">
                    <div className="mx-4 min-w-[36px] min-h-[24px] flex items-center justify-center">
                        <TailSpin
                            color="black"
                            height={16}
                            width={16} />
                    </div>
                </div>
            )}
            {trip && trips ? (
                <>
                    {nextTrip && (nextTrip.start < Date.now() / 1000 || isAdmin) && (
                        <div>
                            <Link
                                className="flex w-full text-center items-center justify-center px-4 py-2 bg-white rounded-lg shadow text-sm font-medium hover:bg-gray-100 transition"
                                to={`/trip/${nextTrip.id}`}>
                                {nextTrip.getFullName()}
                                <MoveRight
                                    className="ml-2"
                                    size={16} />
                            </Link>
                        </div>)}
                </>
            ) : (
                <div
                    className="flex w-full lg:w-auto text-center items-center justify-center px-4 py-2 bg-white rounded-lg shadow text-sm font-medium hover:bg-gray-100 transition">
                    <div className="mx-4 min-w-[36px] min-h-[24px] flex items-center justify-center">
                        <TailSpin
                            color="black"
                            height={16}
                            width={16} />
                    </div>
                </div>
            )}
        </div>
    )
}