import { useMemo } from "react"

import { createTimeTrackingEvent, listTimeTrackingEvents, removeTimeTrackingEvent } from "../clients/coreClient.ts"
import type { TimeTrackingEventType } from "../types/CoreSwaggerTypes.ts"
import type { UseTimeTrackingEventsResult } from "../types/UseTimeTrackingEventsResult.ts"
import { ONE_HOUR_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

// TODO: Rewrite using useQueries from @tanstack/react-query to avoid calling hooks inside a .map() callback.
export const useTimeTrackingEvents = (allowedTypes: TimeTrackingEventType[]): UseTimeTrackingEventsResult => {
    const queries = allowedTypes.map(type =>
        // eslint-disable-next-line react-hooks/rules-of-hooks
        useQuery({
            queryKey: ["listTimeTrackingEvents", type],
            queryFn: () => listTimeTrackingEvents({ type }),
            staleTime: ONE_HOUR_SECONDS * 1000
        })
    )

    const refetchTimeTrackingEvents = async <T>(input: T) => {
        await Promise.all(queries.map(query => query.refetchResponse(query)))
        return input
    }

    return {
        timeTrackingEvents: useMemo(() => allowedTypes.reduce((acc, type, i) => ({ ...acc, [type]: queries[i].response }), {}), [allowedTypes, ...queries.map(q => q.response)]),
        createTimeTrackingEvent: (type: TimeTrackingEventType, description: string, hours: number, timestamp: number) => createTimeTrackingEvent(type, hours, description, timestamp).then(refetchTimeTrackingEvents),
        removeTimeTrackingEvent: (eventId: string) => removeTimeTrackingEvent(eventId).then(refetchTimeTrackingEvents)
    }
}
