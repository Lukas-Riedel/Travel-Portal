import type { Place } from "./CoreSwaggerTypes.ts"

export interface UseRegularPlacesResult {
    places: Place[] | null
    createPermanentPlace: (name: string, address: string) => Promise<Place>
    removePermanentPlace: (placeId: string) => Promise<void>
}