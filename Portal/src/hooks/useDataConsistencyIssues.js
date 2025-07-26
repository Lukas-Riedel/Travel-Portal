import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"

export const useDataConsistencyIssues = () => {
    const { listDataConsistencyIssues } = useApi()
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listDataConsistencyIssues"],
        queryFn: listDataConsistencyIssues,
        staleTime: isAdmin ? 0 : 1000 * 60 * 60,
    })

    // TODO: Map to DataConsistencyIssue objects
    return query.data
}