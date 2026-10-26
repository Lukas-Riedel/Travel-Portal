import type { Coordinates } from "./Coordinates.ts"
import type { DistanceAwarePlace } from "./DistanceAwarePlace.ts"

export interface UseCandidatePlacesResult {
    candidatePlaces: DistanceAwarePlace[] | null
    changeCurrentLocation: (location: Coordinates) => void
    createCandidatePlace: (name: string, address: string) => Promise<DistanceAwarePlace>
    removeCandidatePlace: (placeId: string) => Promise<void>
}
