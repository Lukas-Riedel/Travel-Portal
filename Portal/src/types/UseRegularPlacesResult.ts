import type { Place } from "../classes/Place.ts"

export interface UseRegularPlacesResult {
    places?: Place[]
    createPermanentPlace: (name: string, address: string) => Promise<Place>
    removePermanentPlace: (placeId: string) => Promise<void>
}