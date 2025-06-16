import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"

export const useYear = (year) => {
    const api = useApi()
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["getYear", year],
        queryFn: () => api.getYear(year),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 2,
    })

    const setYear = year => queryClient.setQueryData(["useQuery", year], year)
    const refetchYear = _ => query.refetch()

    return {
        // TODO: Map to Year object
        year: query.data,
        removeYearHighlight: highlightId => api.removeYearHighlight(year, highlightId).then(refetchYear),
        updateYearMainHighlight: highlightId => api.updateYearMainHighlight(year, highlightId).then(setYear),
    }
}