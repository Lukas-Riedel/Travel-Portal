import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"

export const useSubscriptions = () => {
    const api = useApi()
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listSubscriptions"],
        queryFn: () => api.listSubscriptions(),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 12,
    })
    
    // TODO: Map to Statistics objects
    return query.data
}