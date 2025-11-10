
import { getLabel, updateLabelName } from "../clients/coreClient.ts"
import type { UseLabelResult } from "../types/UseLabelResult.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const useLabel = (labelId?: string): UseLabelResult => {
    const { response, setResponse } = useQuery({
        queryKey: ["getLabel", labelId],
        queryFn: () => getLabel(labelId),
        enabled: !!labelId,
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return {
        label: response,
        updateLabelName: (name: string) => updateLabelName(labelId, name).then(setResponse)
    }
}