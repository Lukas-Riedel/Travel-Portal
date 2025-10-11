import { useQuery } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import { listRegions } from "../clients/coreClient"

export const useRegions = ({ name } = {}) => {
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listRegions", name],
        queryFn: () => listRegions({ name }),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 24
    })

    // TODO: Map to Region objects
    return query.data
}