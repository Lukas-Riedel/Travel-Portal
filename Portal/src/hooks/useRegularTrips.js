import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import Trip from "../model/trip"

export const useRegularTrips = ({ year, include } = {}) => {
    const api = useApi()
    const { isAdmin } = useAuth()

    const validity = 60 * 60 * 2
    const query = useQuery({
        queryKey: ["listRegularTrips", year, include],
        queryFn: () => api.listRegularTrips({ year, include }),
        staleTime: isAdmin ? 0 : 1000 * validity,
    })

    return query.data && query.data.map(trip => new Trip(trip))
}