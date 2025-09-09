import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"

export const useAirports = () => {
    const { listAirports, updateAirportName } = useApi()
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listAirports"],
        queryFn: listAirports,
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 12
    })

    const refetchAirports = _ => query.refetch()

    return {
        // TODO: Map to Airport object
        airports: query.data,
        updateAirportName: (airportId, longName) => updateAirportName(airportId, longName).then(refetchAirports)
    }
}