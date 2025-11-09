import { useQuery } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import { listRegularTrips } from "../clients/coreClient"
import { Trip } from "../classes/Trip.ts"

// TODO: This accepts string now, make it accept TripIncludedEntity[]
export const useRegularTrips = ({ year, include } = {}) => {
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listRegularTrips", year, include],
        queryFn: () => listRegularTrips({ year, include: include?.split(",") }),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 2
    })

    return query.data && query.data.map(trip => new Trip(trip))
}