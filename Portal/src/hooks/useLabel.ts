
import { createLabelHighlight, getLabel, refreshLabelHighlights, removeLabelHighlight, updateHighlightQualityAttributes,updateLabelMainHighlight, updateLabelMetadata, updateLabelName } from "../clients/coreClient.ts"
import type { LabelMetadata } from "../types/CoreSwaggerTypes.ts"
import type { UseLabelResult } from "../types/UseLabelResult.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const useLabel = (labelId?: string): UseLabelResult => {
    const { response, setResponse, refetchResponse } = useQuery({
        queryKey: ["getLabel", labelId],
        queryFn: () => getLabel(labelId!),
        enabled: !!labelId,
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return {
        label: response,
        updateLabelName: (name: string) => updateLabelName(labelId!, name).then(setResponse),
        updateLabelMetadata: (metadata: LabelMetadata) => updateLabelMetadata(labelId!, metadata).then(setResponse),
        updateLabelMainHighlight: (highlightId: string) => updateLabelMainHighlight(labelId!, highlightId).then(setResponse),
        createLabelHighlight: (photoId: string) => createLabelHighlight(labelId!, photoId).then(refetchResponse),
        removeLabelHighlight: (highlightId: string) => removeLabelHighlight(labelId!, highlightId).then(refetchResponse),
        updateLabelHighlightQualityAttributes: (highlightId: string, composition: number | null, sky: number | null, shadows: number | null, circumstances: number | null, atmosphere: number | null, impression: number | null) =>
            updateHighlightQualityAttributes(highlightId, composition, sky, shadows, circumstances, atmosphere, impression).then(refetchResponse),
        refreshLabelHighlights: (count: number) => refreshLabelHighlights(labelId!, count).then(refetchResponse)
    }
}