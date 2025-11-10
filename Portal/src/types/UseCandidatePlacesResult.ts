import type { DistanceAwarePlace } from "../classes/DistanceAwarePlace.ts"
import type { Coordinates } from "./Coordinates.ts"

export interface UseCandidatePlacesResult {
    candidatePlaces?: DistanceAwarePlace[]
    changeCurrentLocation: (location: Coordinates) => void
    createCandidatePlace: (name: string, address: string) => Promise<void>
    removeCandidatePlace: (placeId: string) => Promise<void>
}