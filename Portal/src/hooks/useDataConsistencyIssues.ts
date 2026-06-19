import { listDataConsistencyIssues } from "../clients/coreClient.ts"
import type { UseDataConsistencyIssuesResult } from "../types/UseDataConsistencyIssuesResult.ts"
import { ONE_HOUR_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const useDataConsistencyIssues = (enabled?: boolean): UseDataConsistencyIssuesResult => {
    const { response } = useQuery({
        queryKey: ["listDataConsistencyIssues"],
        queryFn: listDataConsistencyIssues,
        staleTime: ONE_HOUR_SECONDS * 1000,
        enabled
    })

    return response
}