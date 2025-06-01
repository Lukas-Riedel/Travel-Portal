import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import Place from "../model/place"

export const usePlace = (placeId) => {
    const api = useApi()
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["getPlace", placeId],
        queryFn: () => api.getPlace(placeId),
        staleTime: isAdmin() ? 0 : 1000 * 60 * 60 * 2,
    })

    return query.data ? new Place(query.data) : null
}