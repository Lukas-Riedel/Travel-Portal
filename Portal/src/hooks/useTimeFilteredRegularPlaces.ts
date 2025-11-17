import { useAuth } from "../contexts/AuthContext.jsx"
import type { PlaceIncludedEntity, PlaceSortingStrategy } from "../types/CoreSwaggerTypes.ts"
import type { UseTimeFilteredRegularPlacesResult } from "../types/UseTimeFilteredRegularPlacesResult.ts"
import { getCurrentTimestamp } from "../utils/timeUtils.ts"
import { useRegularPlaces } from "./useRegularPlaces.ts"

interface UseTimeFilteredRegularPlacesProps {
    tripId?: string
    categoryId?: string
    labelId?: string
    year?: number
    albumId?: string
    photoId?: string
    minStart?: number
    maxEnd?: number
    limit?: number
    include?: PlaceIncludedEntity[]
    sort?: PlaceSortingStrategy
}

export const useTimeFilteredRegularPlaces = ({ tripId, categoryId, labelId, year, albumId, photoId, minStart, maxEnd, limit, include, sort }: UseTimeFilteredRegularPlacesProps = {}): UseTimeFilteredRegularPlacesResult => {
    const { isAdmin } = useAuth()

    const adjustedMaxEnd = Math.min(maxEnd ?? Number.MAX_SAFE_INTEGER, isAdmin ? Number.MAX_SAFE_INTEGER : getCurrentTimestamp())
    return useRegularPlaces({ tripId, categoryId, labelId, year, albumId, photoId, minStart, maxEnd: adjustedMaxEnd, limit, include, sort })
}