import { listLabels } from "../clients/coreClient.ts"
import type { UseLabelsResult } from "../types/UseLabelsResult.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const useLabels = (): UseLabelsResult => {
    const { response } = useQuery({
        queryKey: ["listLabels"],
        queryFn: listLabels,
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return response
}