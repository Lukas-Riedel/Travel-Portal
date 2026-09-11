import type { UseUpcomingOrCurrentTripResult } from "../types/UseUpcomingOrCurrentTripResult.ts"
import { isCurrentTrip, isFutureTrip } from "../utils/tripUtils.ts"
import { useRegularTrips } from "./useRegularTrips.js"
import { useTrip } from "./useTrip.ts"

export const useUpcomingOrCurrentTrip = (): UseUpcomingOrCurrentTripResult => {
    const { trips } = useRegularTrips()
    const upcomingOrCurrentTripId = trips?.find(trip => isCurrentTrip(trip) || isFutureTrip(trip))?.id

    return useTrip(upcomingOrCurrentTripId)
}