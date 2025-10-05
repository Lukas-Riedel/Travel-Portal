import { useQuery } from "@tanstack/react-query"
import { listAirlines, createAirline, updateAirlineName, updateAirlineLogo, removeAirline, removeAirlineCode } from "../clients/coreClient"
import { useAuth } from "../contexts/AuthContext"

export const useAirlines = () => {
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listAirlines"],
        queryFn: listAirlines,
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 24
    })
    
    const refetchAirlines = _ => query.refetch()

    return {
        // TODO: Map to Airline objects
        airlines: query.data,
        createAirline: name => createAirline(name).then(refetchAirlines),
        updateAirlineName: (airlineId, name) => updateAirlineName(airlineId, name).then(refetchAirlines),
        updateAirlineLogo: (airlineId, logo) => updateAirlineLogo(airlineId, logo).then(refetchAirlines),
        removeAirline: airlineId => removeAirline(airlineId).then(refetchAirlines),
        removeAirlineCode: (airlineId, code) => removeAirlineCode(airlineId, code).then(refetchAirlines)
    }
}