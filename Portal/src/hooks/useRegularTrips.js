import { useQuery } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import Trip from "../model/trip"
import { listRegularTrips } from "../clients/coreClient"

export const useRegularTrips = ({ year, include } = {}) => {
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listRegularTrips", year, include],
        queryFn: () => listRegularTrips({ year, include }),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 2
    })

    return query.data && query.data.map(trip => new Trip(trip))
}