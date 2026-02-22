import { useMemo } from "react"
import { useRegularTrips } from "./useRegularTrips.js"
import { useTrip } from "./useTrip.ts"
import type { UseUpcomingOrCurrentTripResult } from "../types/UseUpcomingOrCurrentTripResult.ts"

export const useUpcomingOrCurrentTrip = (): UseUpcomingOrCurrentTripResult => {
    const trips = useRegularTrips()
    const upcomingOrCurrentTripId = useMemo(() => trips?.find(trip => trip.isCurrent() || trip.isFuture())?.id, [trips])
    
    return useTrip(upcomingOrCurrentTripId)
}