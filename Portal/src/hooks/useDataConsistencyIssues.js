import { useQuery } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import { listDataConsistencyIssues } from "../clients/coreClient"

export const useDataConsistencyIssues = () => {
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listDataConsistencyIssues"],
        queryFn: listDataConsistencyIssues,
        staleTime: isAdmin ? 0 : 1000 * 60 * 60
    })

    // TODO: Map to DataConsistencyIssue objects
    return query.data
}