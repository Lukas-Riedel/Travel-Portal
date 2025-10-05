import { useQuery } from "@tanstack/react-query"
import { listAirports, updateAirportLongName } from "../clients/coreClient"
import { useAuth } from "../contexts/AuthContext"

export const useAirports = () => {
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
        updateAirportLongName: (airportId, longName) => updateAirportLongName(airportId, longName).then(refetchAirports)
    }
}