import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import Place from "../model/place"

export const useRegularPlaces = ({ tripId, categoryId, labelName, year, minStart, maxEnd, include, sort } = {}) => {
    const api = useApi()
    const { isAdmin } = useAuth()

    const validity = 60 * 60 * 2
    const query = useQuery({
        queryKey: ["listRegularPlaces", tripId, categoryId, labelName, year, minStart - (minStart % validity), maxEnd - (maxEnd % validity), include, sort],
        queryFn: () => api.listRegularPlaces({ tripId, categoryId, labelName, year, minStart, maxEnd, include, sort }),
        staleTime: isAdmin ? 0 : 1000 * validity
    })
    
    return query.data && query.data.map(place => new Place(place))
}