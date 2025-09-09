import { useQuery, useQueryClient } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"

export const useAirport = airportId => {
    const { getAirport, updateAirportName } = useApi()
    const { isAdmin } = useAuth()

    const queryClient = useQueryClient()

    const query = useQuery({
        queryKey: ["getAirport", airportId],
        queryFn: () => getAirport(airportId),
        enabled: !!airportId,
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 24
    })

    const setAirport = airport => queryClient.setQueryData(["getAirport", airportId], airport)

    return {
        // TODO: Map to Airport object
        airport: query.data,
        updateAirportName: name => updateAirportName(airportId, name).then(setAirport)
    }
}