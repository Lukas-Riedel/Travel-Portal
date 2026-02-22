import { listRegularTrips } from "../clients/coreClient.ts"
import { Trip } from "../classes/Trip.ts"
import type { TripIncludedEntity } from "../types/CoreSwaggerTypes.ts"
import { useQuery } from "./useQuery.ts"
import { ONE_HOUR_SECONDS } from "../utils/timeUtils.ts"
import type { UseRegularTripsResult } from "../types/UseRegularTripsResult.ts"
import { useMemo } from "react"

interface UseRegularTripsProps {
    year?: number
    include?: TripIncludedEntity[]
}

export const useRegularTrips = ({ year, include }: UseRegularTripsProps = {}): UseRegularTripsResult => {
    const { response } = useQuery({
        queryKey: ["listRegularTrips", `${year}`, ...(include ?? [])],
        queryFn: () => listRegularTrips({ year, include }),
        staleTime: ONE_HOUR_SECONDS * 1000
    })

    return useMemo(() => response?.map(trip => new Trip(trip)), [response])
}