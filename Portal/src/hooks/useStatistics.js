import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"

export const useStatistics = () => {
    const { listStatistics } = useApi()
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listStatistics"],
        queryFn: listStatistics,
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 12
    })

    // TODO: Map to Statistics objects
    return query.data
}