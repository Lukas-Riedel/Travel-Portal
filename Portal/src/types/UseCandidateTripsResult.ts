import type { Trip } from "../classes/Trip.ts"

export interface UseCandidateTripsResult {
    trips: Trip[] | null
    removeTrip: (tripId: string) => Promise<void>
}