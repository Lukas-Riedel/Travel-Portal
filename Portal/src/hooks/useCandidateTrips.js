import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import Trip from "../model/trip"

export const useCandidateTrips = ({ include } = {}) => {
    const api = useApi()
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listCandidateTrips", include],
        queryFn: () => api.listCandidateTrips({ include }),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 2,
    })
    
    const refetchCandidateTrips = _ => query.refetch()

    return {
        candidateTrips: query.data && query.data.map(trip => new Trip(trip)),
        removeCandidateTrip: tripId => api.removeTrip(tripId).then(refetchCandidateTrips)
    }
}