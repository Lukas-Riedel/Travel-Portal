import { useQuery, useQueryClient } from "@tanstack/react-query"
import { getYear, removeYearHighlight, updateYearMainHighlight, updateHighlightQualityAttributes, createYearHighlight } from "../clients/coreClient"
import { useAuth } from "../contexts/AuthContext"

export const useYear = year => {
    const { isAdmin } = useAuth()

    const queryClient = useQueryClient()

    const query = useQuery({
        queryKey: ["getYear", year],
        queryFn: () => getYear(year),
        enabled: !!year,
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 12
    })

    const setYear = year => queryClient.setQueryData(["useQuery", year], year)
    const refetchYear = _ => query.refetch()

    return {
        // TODO: Map to Year object
        year: query.data,
        createYearHighlight: photoId => createYearHighlight(year, photoId).then(refetchYear),
        removeYearHighlight: highlightId => removeYearHighlight(year, highlightId).then(refetchYear),
        updateYearMainHighlight: highlightId => updateYearMainHighlight(year, highlightId).then(setYear),
        updateYearHighlightQualityAttributes: (highlightId, composition, sky, shadows, circumstances, atmosphere) =>
            updateHighlightQualityAttributes(highlightId, composition, sky, shadows, circumstances, atmosphere).then(refetchYear)
    }
}