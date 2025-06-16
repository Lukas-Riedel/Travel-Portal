import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"

export const useYears = ({ include } = {}) => {
    const api = useApi()
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listYears", include],
        queryFn: () => api.listYears({ include }),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 24,
    })
    
    // TODO: Map to Year objects
    return query.data
}