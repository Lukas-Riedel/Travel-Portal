import { useMemo } from "react"
import { useRegularTrips } from "./useRegularTrips"
import { useTrip } from "./useTrip"

export const useUpcomingOrCurrentTrip = () => {
    const trips = useRegularTrips()
    const upcomingOrCurrentTripId = useMemo(() => trips?.find(trip => !trip?.isDayTrips() && (trip?.isCurrent() || trip?.isFuture()))?.id, [trips])
    
    return useTrip(upcomingOrCurrentTripId)
}