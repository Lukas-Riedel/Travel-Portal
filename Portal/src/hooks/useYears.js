import { useQuery } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import { listYears } from "../clients/coreClient"

// TODO: This accepts string now, make it accept CategoryIncludedEntity[]
export const useYears = ({ include } = {}) => {
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listYears", include],
        queryFn: () => listYears({ include: include?.split(",") }),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 24,
    })

    // TODO: Map to Year objects
    return query.data
}