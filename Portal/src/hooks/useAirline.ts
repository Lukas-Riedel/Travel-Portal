import { getAirline, removeAirline, updateAirlineName, updateAirlineLogo } from "../clients/coreClient.ts"
import type { UseAirlineResult } from "../types/UseAirlineResult.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const useAirline = (airlineId?: string): UseAirlineResult => {
    const { response, setResponse } = useQuery({
        queryKey: ["getAirline", airlineId],
        queryFn: () => getAirline(airlineId),
        enabled: !!airlineId,
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return {
        airline: response,
        updateAirlineName: name => updateAirlineName(airlineId, name).then(setResponse),
        updateAirlineLogo: logo => updateAirlineLogo(airlineId, logo).then(setResponse),
        removeAirline: () => removeAirline(airlineId)
    }
}