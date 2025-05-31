import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import { getMaxEndTimestamp } from "../utils/helpers"

export const useRegularPlaces = () => {
    const { isAdmin } = useAuth()
    const api = useApi()

    return useQuery({
        queryKey: ["regularPlaces"],
        // TODO: Remove the "DATES" scope after moving score to backend functions
        queryFn: () => api.listRegularPlaces(undefined, undefined, undefined, undefined, undefined, getMaxEndTimestamp(isAdmin()), "CATEGORIES,DATES"),
        staleTime: 1000 * 60 * 15,
    })
}