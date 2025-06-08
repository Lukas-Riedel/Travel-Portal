import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import Place from "../model/place"

export const useCandidatePlaces = ({ tripId, categoryId, include, sort } = {}) => {
    const api = useApi()
    const { isAdmin } = useAuth()

    const validity = 60 * 60 * 2
    const query = useQuery({
        queryKey: ["listCandidatePlaces", tripId, categoryId, include, sort],
        queryFn: () => api.listCandidatePlaces({ tripId, categoryId, include, sort }),
        staleTime: isAdmin ? 0 : 1000 * validity,
    })
    
    return query.data && query.data.map(place => new Place(place))
}