import { useQuery, useQueryClient } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"

export const useAirline = airlineId => {
    const { getAirline, removeAirline, updateAirlineName, updateAirlineLogo } = useApi()
    const { isAdmin } = useAuth()

    const queryClient = useQueryClient()

    const query = useQuery({
        queryKey: ["getAirline", airlineId],
        queryFn: () => getAirline(airlineId),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 24,
    })

    const setAirline = airline => queryClient.setQueryData(["getAirline", airlineId], airline)

    return {
        // TODO: Map to Airline object
        airline: query.data,
        updateAirlineName: name => updateAirlineName(airlineId, name).then(setAirline),
        updateAirlineLogo: logo => updateAirlineLogo(airlineId, logo).then(setAirline),
        removeAirline: _ => removeAirline(airlineId)
    }
}