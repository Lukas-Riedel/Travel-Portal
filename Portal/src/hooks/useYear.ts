
import { getYear, removeYearHighlight, updateYearMainHighlight, updateHighlightQualityAttributes, createYearHighlight, refreshYearHighlights } from "../clients/coreClient.ts"
import type { UseYearResult } from "../types/UseYearResult.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const useYear = (year?: number): UseYearResult => {
    const { response, setResponse, refetchResponse } = useQuery({
        queryKey: ["getYear", `${year}`],
        queryFn: () => getYear(year),
        enabled: !!year,
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return {
        year: response,
        createYearHighlight: (photoId: string) => createYearHighlight(year, photoId).then(refetchResponse),
        removeYearHighlight: (highlightId: string) => removeYearHighlight(year, highlightId).then(refetchResponse),
        updateYearMainHighlight: (highlightId: string) => updateYearMainHighlight(year, highlightId).then(setResponse),
        updateYearHighlightQualityAttributes: (highlightId: string, composition: number | null, sky: number | null, shadows: number | null, circumstances: number | null, atmosphere: number | null, impression: number | null) =>
            updateHighlightQualityAttributes(highlightId, composition, sky, shadows, circumstances, atmosphere, impression).then(refetchResponse),
        refreshYearHighlights: (count: number) => refreshYearHighlights(year, count).then(refetchResponse)
    }
}