import { useQuery } from "@tanstack/react-query"
import { listCandidateTrips, removeTrip } from "../clients/coreClient"
import { useAuth } from "../contexts/AuthContext"
import Trip from "../model/trip"

// TODO: This accepts string now, make it accept TripIncludedEntity[]
export const useCandidateTrips = ({ include } = {}) => {
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listCandidateTrips", include],
        queryFn: () => listCandidateTrips({ include: include?.split(",") }),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 2
    })

    const refetchCandidateTrips = _ => query.refetch()

    return {
        candidateTrips: query.data && query.data.map(trip => new Trip(trip)),
        removeCandidateTrip: tripId => removeTrip(tripId).then(refetchCandidateTrips)
    }
}