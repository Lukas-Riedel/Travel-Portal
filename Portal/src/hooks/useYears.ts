import { listYears } from "../clients/coreClient.ts"
import type { YearIncludedEntity } from "../types/CoreSwaggerTypes.ts"
import type { UseYearsResult } from "../types/UseYearsResult.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const useYears = ({ include }: { include?: YearIncludedEntity[] } = {}): UseYearsResult => {
    const { response } = useQuery({
        queryKey: ["listYears", ...(include ?? [])],
        queryFn: () => listYears({ include }),
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return response
}