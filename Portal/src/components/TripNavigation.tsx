import { useMemo } from "react"
import { useRegularTrips } from "../hooks/useRegularTrips.ts"
import { Link } from "react-router-dom"
import { MoveLeft, MoveRight, type LucideIcon } from "lucide-react"
import { useAuth } from "../contexts/AuthContext.tsx"
import { TailSpin } from "react-loader-spinner"
import { UserRole } from "../types/CoreSwaggerTypes.ts"
import type { Trip } from "../classes/Trip.ts"
import { getCurrentOrMaximumAllowedTimestamp } from "../utils/timeUtils.ts"
import AppLink from "./AppLink.tsx"

interface TripNavigationProps {
    trip: Trip | null
    canDisplayFutureTrips: boolean
}

export default function TripNavigation({ trip, canDisplayFutureTrips }: TripNavigationProps) {
    const { trips } = useRegularTrips()

    const previousTrip = useMemo(() => trip?.isCandidate() ? undefined : trips?.findLast(t => t?.start < trip?.start), [trip, trips])
    const nextTrip = useMemo(() => trip?.isCandidate() ? undefined : trips?.find(t => t?.start > trip?.start), [trip, trips])

    return (
        <div className="flex flex-col lg:flex-row lg:justify-between p-6 my-4 space-y-4 lg:space-y-0">
            <TripLink
                trip={previousTrip}
                isLoaded={!!(trip && trips)}
                canDisplayFutureTrips={canDisplayFutureTrips}>
                <MoveLeft
                    className="mr-2"
                    size={16} />
                {previousTrip?.getFullName()}
            </TripLink>

            <TripLink
                trip={nextTrip}
                isLoaded={!!(trip && trips)}
                canDisplayFutureTrips={canDisplayFutureTrips}>
                {nextTrip?.getFullName()}
                <MoveRight
                    className="ml-2"
                    size={16} />
            </TripLink>
        </div>
    )
}

interface TripLinkProps {
    trip: Trip | null
    isLoaded: boolean
    canDisplayFutureTrips: boolean
    children: React.ReactNode
}

function TripLink({ trip, isLoaded, canDisplayFutureTrips, children }: TripLinkProps) {
    return isLoaded ? (
        <>
            {trip && (trip.start < getCurrentOrMaximumAllowedTimestamp() || canDisplayFutureTrips) && (
                <div>
                    <AppLink
                        className="flex w-full text-center items-center justify-center px-4 py-2 bg-white rounded-lg shadow text-sm font-medium hover:bg-gray-100 transition"
                        to={trip}>
                        {children}
                    </AppLink>
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
    )
}