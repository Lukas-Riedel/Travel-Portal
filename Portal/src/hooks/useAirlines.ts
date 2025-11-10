import { listAirlines, createAirline, updateAirlineName, updateAirlineLogo, removeAirline, removeAirlineCode, createAirlineCode } from "../clients/coreClient.ts"
import type { UseAirlinesResult } from "../types/UseAirlinesResult.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const useAirlines = (): UseAirlinesResult => {
    const { response, refetchResponse } = useQuery({
        queryKey: ["listAirlines"],
        queryFn: listAirlines,
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return {
        airlines: response,
        createAirline: (name: string) => createAirline(name).then(refetchResponse),
        createAirlineCode: (airlineId: string, code: string) => createAirlineCode(airlineId, code).then(refetchResponse),
        updateAirlineName: (airlineId: string, name: string) => updateAirlineName(airlineId, name).then(refetchResponse),
        updateAirlineLogo: (airlineId: string, logo: string) => updateAirlineLogo(airlineId, logo).then(refetchResponse),
        removeAirline: (airlineId: string) => removeAirline(airlineId).then(refetchResponse),
        removeAirlineCode: (airlineId: string, code: string) => removeAirlineCode(airlineId, code).then(refetchResponse)
    }
}