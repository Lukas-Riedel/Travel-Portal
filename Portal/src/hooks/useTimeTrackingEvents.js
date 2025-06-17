import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"

export const useTimeTrackingEvents = ({ type } = {}) => {
    const api = useApi()
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listTimeTrackingEvents", type],
        queryFn: () => api.listTimeTrackingEvents({ type }),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 4,
    })

    // TODO: Map to TimeTrackingEvent objects
    return query.data
}