import { listStatistics } from "../clients/coreClient.ts"
import type { UseStatisticsResult } from "../types/UseStatisticsResult.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const useStatistics = (): UseStatisticsResult => {
    const { response } = useQuery({
        queryKey: ["listStatistics"],
        queryFn: listStatistics,
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return response
}