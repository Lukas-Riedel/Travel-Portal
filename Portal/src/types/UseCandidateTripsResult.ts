import type { Trip } from "./CoreSwaggerTypes.ts"

export interface UseCandidateTripsResult {
    trips: Trip[] | null
    removeTrip: (tripId: string) => Promise<void>
}