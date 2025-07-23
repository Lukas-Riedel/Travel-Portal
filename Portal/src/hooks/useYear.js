import { useQuery, useQueryClient } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"

export const useYear = (year) => {
    const { getYear, removeYearHighlight, updateYearMainHighlight, updateHighlightQualityAttributes } = useApi()
    const { isAdmin } = useAuth()

    const queryClient = useQueryClient()

    const query = useQuery({
        queryKey: ["getYear", year],
        queryFn: () => getYear(year),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 12,
    })

    const setYear = year => queryClient.setQueryData(["useQuery", year], year)
    const refetchYear = _ => query.refetch()

    return {
        // TODO: Map to Year object
        year: query.data,
        removeYearHighlight: highlightId => removeYearHighlight(year, highlightId).then(refetchYear),
        updateYearMainHighlight: highlightId => updateYearMainHighlight(year, highlightId).then(setYear),
        updateYearHighlightQualityAttributes: (highlightId, composition, sky, shadows, circumstances, atmosphere) =>
            updateHighlightQualityAttributes(highlightId, composition, sky, shadows, circumstances, atmosphere).then(refetchYear)
    }
}