import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import { useMemo } from "react"

export const useTimeTrackingEvents = allowedTypes => {
    const { listTimeTrackingEvents, removeTimeTrackingEvent, createTimeTrackingEvent } = useApi()
    const { isAdmin } = useAuth()

    const queries = allowedTypes.map(type =>
        useQuery({
            queryKey: ["listTimeTrackingEvents", type],
            queryFn: () => listTimeTrackingEvents({ type }),
            staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 4
        })
    )

    const refetchTimeTrackingEvents = () => Promise.all(queries.map(query => query.refetch()))

    return {
        // TODO: Map to TimeTrackingEvent objects
        timeTrackingEvents: useMemo(() => allowedTypes.reduce((acc, type, i) => ({ ...acc, [type]: queries[i].data }), {}), [allowedTypes, ...queries.map(q => q.data)]),
        removeTimeTrackingEvent: eventId => removeTimeTrackingEvent(eventId).then(refetchTimeTrackingEvents),
        createTimeTrackingEvent: (type, description, hours, timestamp) => createTimeTrackingEvent(type, hours, description, timestamp).then(refetchTimeTrackingEvents)
    }
}