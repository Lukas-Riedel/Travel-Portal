import { useQuery } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import { listSubscriptions } from "../clients/coreClient"

export const useSubscriptions = () => {
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listSubscriptions"],
        queryFn: listSubscriptions,
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 12
    })

    // TODO: Map to Statistics objects
    return query.data
}