import { listTimeTrackingEvents, removeTimeTrackingEvent, createTimeTrackingEvent } from "../clients/coreClient.ts"
import { useMemo } from "react"
import type { TimeTrackingEventType } from "../types/CoreSwaggerTypes.ts"
import { useQuery } from "./useQuery.ts"
import { ONE_HOUR_SECONDS } from "../utils/timeUtils.ts"
import type { UseTimeTrackingEventsResult } from "../types/UseTimeTrackingEventsResult.ts"

export const useTimeTrackingEvents = (allowedTypes: TimeTrackingEventType[]): UseTimeTrackingEventsResult => {
    const queries = allowedTypes.map(type =>
        useQuery({
            queryKey: ["listTimeTrackingEvents", type],
            queryFn: () => listTimeTrackingEvents({ type }),
            staleTime: ONE_HOUR_SECONDS * 1000
        })
    )

    const refetchTimeTrackingEvents = async () => {
        await Promise.all(queries.map(query => query.refetchResponse(query)))
    }

    return {
        timeTrackingEvents: useMemo(() => allowedTypes.reduce((acc, type, i) => ({ ...acc, [type]: queries[i].response }), {}), [allowedTypes, ...queries.map(q => q.response)]),
        createTimeTrackingEvent: (type: TimeTrackingEventType, description: string, hours: number, timestamp: number) => createTimeTrackingEvent(type, hours, description, timestamp).then(refetchTimeTrackingEvents),
        removeTimeTrackingEvent: (eventId: string) => removeTimeTrackingEvent(eventId).then(refetchTimeTrackingEvents)
    }
}