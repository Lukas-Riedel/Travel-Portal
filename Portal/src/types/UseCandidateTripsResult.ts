import type { Trip } from "../classes/Trip.ts"

export interface UseCandidateTripsResult {
    trips?: Trip[]
    removeTrip: (tripId: string) => Promise<void>
}