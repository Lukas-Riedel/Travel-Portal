import { listAirports, updateAirportCountry, updateAirportLongName } from "../clients/coreClient.ts"
import type { UseAirportsResult } from "../types/UseAirportsResult.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const useAirports = (): UseAirportsResult => {

    const { response, refetchResponse } = useQuery({
        queryKey: ["listAirports"],
        queryFn: listAirports,
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return {
        airports: response,
        updateAirportLongName: (airportId: string, longName: string) => updateAirportLongName(airportId, longName).then(refetchResponse),
        updateAirportCountry: (airportId: string, country: string) => updateAirportCountry(airportId, country).then(refetchResponse)
    }
}