import { createLifestyleEvent, listLifestyleEvents, removeLifestyleEvent } from "../clients/coreClient.ts"
import type { LifestyleEventType } from "../types/CoreSwaggerTypes.ts"
import type { UseLifestyleEventsResult } from "../types/UseLifestyleEventsResult.ts"
import { ONE_HOUR_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const useLifestyleEvents = (from: number): UseLifestyleEventsResult => {
    const { response: events, refetchResponse } = useQuery({
        queryKey: ["listLifestyleEvents", String(from)],
        queryFn: () => listLifestyleEvents({ from }),
        staleTime: ONE_HOUR_SECONDS * 1000
    })

    return {
        events,
        createLifestyleEvent: (type: LifestyleEventType, content: string, timestamp: number, calories?: number) =>
            createLifestyleEvent(type, content, timestamp, calories).then(refetchResponse),
        removeLifestyleEvent: (eventId: string) => removeLifestyleEvent(eventId).then(refetchResponse)
    }
}
