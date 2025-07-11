import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"

export const useAirlines = () => {
    const { listAirlines } = useApi()
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listAirlines"],
        queryFn: listAirlines,
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 24,
    })

    // TODO: Map to Airline objects
    return query.data
}