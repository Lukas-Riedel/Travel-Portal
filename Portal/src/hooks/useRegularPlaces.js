import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import { getMaxEndTimestamp } from "../utils/helpers"

export const useRegularPlaces = () => {
    const { isAdmin } = useAuth()
    const api = useApi()

    return useQuery({
        queryKey: ["regularPlaces"],
        // TODO: Remove the "CATEGORIES" scope
        queryFn: () => api.listRegularPlaces(undefined, undefined, undefined, undefined, undefined, getMaxEndTimestamp(isAdmin()), "CATEGORIES", "score"),
        staleTime: 1000 * 60 * 15,
    })
}