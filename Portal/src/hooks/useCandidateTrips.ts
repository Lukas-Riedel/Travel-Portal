import { Trip } from "../classes/Trip.ts"
import { listCandidateTrips, removeTrip } from "../clients/coreClient.ts"
import type { TripIncludedEntity } from "../types/CoreSwaggerTypes.ts"
import type { UseCandidateTripsResult } from "../types/UseCandidateTripsResult.ts"
import { ONE_HOUR_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

interface UseCandidateTripsProps {
    include?: TripIncludedEntity[]
}

export const useCandidateTrips = ({ include }: UseCandidateTripsProps = {}): UseCandidateTripsResult => {
    const { response, refetchResponse } = useQuery({
        queryKey: ["listCandidateTrips", ...(include ?? [])],
        queryFn: () => listCandidateTrips({ include }),
        staleTime: ONE_HOUR_SECONDS * 1000
    })

    return {
        trips: response === null ? null : response.map(trip => new Trip(trip)),
        removeTrip: (tripId: string) => removeTrip(tripId).then(refetchResponse)
    }
}