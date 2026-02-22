import { getAirport, updateAirportCountry, updateAirportLongName } from "../clients/coreClient.ts"
import type { UseAirportResult } from "../types/UseAirportResult.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const useAirport = (airportId?: string): UseAirportResult => {
    const { response, setResponse } = useQuery({
        queryKey: ["getAirport", airportId],
        queryFn: () => getAirport(airportId),
        enabled: !!airportId,
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return {
        airport: response,
        updateAirportLongName: (name: string) => updateAirportLongName(airportId, name).then(setResponse),
        updateAirportCountry: (country: string) => updateAirportCountry(airportId, country).then(setResponse)
    }
}