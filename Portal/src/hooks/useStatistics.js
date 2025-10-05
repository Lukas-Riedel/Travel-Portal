import { useQuery } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import { listStatistics } from "../clients/coreClient"

export const useStatistics = () => {
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listStatistics"],
        queryFn: listStatistics,
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 12
    })

    // TODO: Map to Statistics objects
    return query.data
}