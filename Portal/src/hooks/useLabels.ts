import { createLabel, listLabels, removeLabel, updateLabelMetadata } from "../clients/coreClient.ts"
import type { LabelIncludedEntity, LabelMetadata } from "../types/CoreSwaggerTypes.ts"
import type { UseLabelsResult } from "../types/UseLabelsResult.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

interface UseLabelsProps {
    include?: LabelIncludedEntity[]
}

export const useLabels = ({ include }: UseLabelsProps = {}): UseLabelsResult => {
    const { response, refetchResponse } = useQuery({
        queryKey: ["listLabels", ...(include ?? [])],
        queryFn: () => listLabels({ include }),
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return {
        labels: response,
        createLabel: (name: string) => createLabel(name).then(refetchResponse),
        updateLabelMetadata: (labelId: string, metadata: LabelMetadata) => updateLabelMetadata(labelId, metadata).then(refetchResponse),
        removeLabel: (labelId: string) => removeLabel(labelId).then(refetchResponse)
    }
}