import type { Place } from "../classes/Place.ts"

export interface UseRegularPlacesResult {
    places?: Place[]
    createPermanentPlace: (name: string, address: string) => Promise<void>
    removePermanentPlace: (placeId: string) => Promise<void>
}