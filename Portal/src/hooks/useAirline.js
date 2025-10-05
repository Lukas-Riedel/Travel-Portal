import { useQuery, useQueryClient } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import { getAirline, removeAirline, updateAirlineName, updateAirlineLogo } from "../clients/coreClient"

export const useAirline = airlineId => {
    const { isAdmin } = useAuth()

    const queryClient = useQueryClient()

    const query = useQuery({
        queryKey: ["getAirline", airlineId],
        queryFn: () => getAirline(airlineId),
        enabled: !!airlineId,
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 24
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