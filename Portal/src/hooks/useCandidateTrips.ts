import { listCandidateTrips, removeTrip } from "../clients/coreClient.ts"
import { Trip } from "../classes/Trip.ts"
import type { TripIncludedEntity } from "../types/CoreSwaggerTypes.ts"
import { ONE_HOUR_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"
import type { UseCandidateTripsResult } from "../types/UseCandidateTripsResult.ts"

export const useCandidateTrips = ({ include }: { include?: TripIncludedEntity[] } = {}): UseCandidateTripsResult => {
    const { response, refetchResponse } = useQuery({
        queryKey: ["listCandidateTrips", ...(include ?? [])],
        queryFn: () => listCandidateTrips({ include }),
        staleTime: ONE_HOUR_SECONDS * 1000
    })

    return {
        trips: response?.map(trip => new Trip(trip)),
        removeTrip: (tripId: string) => removeTrip(tripId).then(refetchResponse)
    }
}