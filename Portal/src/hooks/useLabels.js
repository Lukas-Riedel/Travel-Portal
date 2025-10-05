import { useQuery } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import { listLabels } from "../clients/coreClient"

export const useLabels = () => {
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listLabels"],
        queryFn: listLabels,
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 12
    })

    // TODO: Map to Label object
    return query.data
}