import { createTripTask, listRegularTrips, removeTripTask, updateTripTaskDescription, updateTripTaskPriority } from "../clients/coreClient.ts"
import { Trip } from "../classes/Trip.ts"
import type { TaskPriority, TripIncludedEntity } from "../types/CoreSwaggerTypes.ts"
import { useQuery } from "./useQuery.ts"
import { ONE_HOUR_SECONDS } from "../utils/timeUtils.ts"
import type { UseRegularTripsResult } from "../types/UseRegularTripsResult.ts"

interface UseRegularTripsProps {
    year?: number
    include?: TripIncludedEntity[]
}

export const useRegularTrips = ({ year, include }: UseRegularTripsProps = {}): UseRegularTripsResult => {
    const { response, refetchResponse } = useQuery({
        queryKey: ["listRegularTrips", `${year}`, ...(include ?? [])],
        queryFn: () => listRegularTrips({ year, include }),
        staleTime: ONE_HOUR_SECONDS * 1000
    })

    return {
        trips: response?.map(trip => new Trip(trip)),
        createTripTask: (tripId: string, description: string, priority: TaskPriority, deadline?: number) => createTripTask(tripId, description, priority, deadline).then(refetchResponse),
        removeTripTask: (tripId: string, taskId: string) => removeTripTask(tripId, taskId).then(refetchResponse),
        updateTripTaskDescription: (tripId: string, taskId: string, newDescription: string) => updateTripTaskDescription(tripId, taskId, newDescription).then(refetchResponse),
        updateTripTaskPriority: (tripId: string, taskId: string, newPriority: TaskPriority) => updateTripTaskPriority(tripId, taskId, newPriority).then(refetchResponse)
    }
}