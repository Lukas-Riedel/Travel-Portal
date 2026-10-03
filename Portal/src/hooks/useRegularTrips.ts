import { createTripTask, listRegularTrips, removeTripTask, updateTripTaskPriority,updateTripTaskTitle } from "../clients/coreClient.ts"
import type { TaskPriority, TripIncludedEntity } from "../types/CoreSwaggerTypes.ts"
import type { UseRegularTripsResult } from "../types/UseRegularTripsResult.ts"
import { ONE_HOUR_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

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
        trips: response === null ? null : response,
        createTripTask: (tripId: string, title: string, priority: TaskPriority, notificationInterval?: number, deadline?: number, description?: string, autoDelete?: boolean) => createTripTask(tripId, title, priority, notificationInterval, deadline, description, autoDelete).then(refetchResponse),
        removeTripTask: (tripId: string, taskId: string) => removeTripTask(tripId, taskId).then(refetchResponse),
        updateTripTaskTitle: (tripId: string, taskId: string, newTitle: string, newDescription?: string) => updateTripTaskTitle(tripId, taskId, newTitle, newDescription).then(refetchResponse),
        updateTripTaskPriority: (tripId: string, taskId: string, newPriority: TaskPriority) => updateTripTaskPriority(tripId, taskId, newPriority).then(refetchResponse)
    }
}