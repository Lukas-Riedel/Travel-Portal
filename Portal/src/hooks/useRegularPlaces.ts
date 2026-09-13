import { useEffect } from "react"

import { createPermanentPlace, listRegularPlaces, removePermanentPlace } from "../clients/coreClient.ts"
import type { PlaceIncludedEntity, PlaceQualityTier, PlaceSortingStrategy } from "../types/CoreSwaggerTypes.ts"
import type { UseRegularPlacesResult } from "../types/UseRegularPlacesResult.ts"
import { ONE_HOUR_SECONDS } from "../utils/timeUtils.ts"
import { useAlbumsBeingUploaded } from "./useAlbumsBeingUploaded.ts"
import { useQuery } from "./useQuery.ts"

const RESPONSE_VALIDITY_SECONDS = ONE_HOUR_SECONDS
const ALBUM_UPLOADING_REFETCH_INTERVAL_SECONDS = 5

interface UseRegularPlacesProps {
    tripId?: string
    categoryId?: string
    labelId?: string
    year?: number
    albumId?: string
    photoId?: string
    minStart?: number
    maxEnd?: number
    maxQuality?: number
    maxTier?: PlaceQualityTier
    nearbyPlaces?: number
    limit?: number
    include?: PlaceIncludedEntity[]
    sort?: PlaceSortingStrategy
    enabled?: boolean
}

export const useRegularPlaces = ({ tripId, categoryId, labelId, year, albumId, photoId, minStart, maxEnd, maxQuality, maxTier, nearbyPlaces, limit, include, sort, enabled }: UseRegularPlacesProps = {}): UseRegularPlacesResult => {
    const { startedUploadingsCount, isBeingUploaded } = useAlbumsBeingUploaded()

    const { response, refetchResponse } = useQuery({
        queryKey: ["listRegularPlaces", tripId, categoryId, labelId, `${year}`, albumId, photoId, `${minStart != null ? minStart - (minStart % RESPONSE_VALIDITY_SECONDS) : ""}`, `${maxEnd != null ? maxEnd - (maxEnd % RESPONSE_VALIDITY_SECONDS) : ""}`, `${maxQuality ?? ""}`, `${maxTier ?? ""}`, `${nearbyPlaces}`, `${limit}`, ...(include ?? []), sort ?? ""],
        queryFn: () => listRegularPlaces({ tripId, categoryId, labelId, year, albumId, photoId, minStart, maxEnd, maxQuality, maxTier, nearbyPlaces, limit, include, sort }),
        staleTime: RESPONSE_VALIDITY_SECONDS * 1000,
        enabled,
        refetchInterval: query => query.state.data?.flatMap(place => place.dates ?? [])?.some(date => (date.album?.uploadingStart && date.album?.uploadingProgress) || isBeingUploaded(date)) && ALBUM_UPLOADING_REFETCH_INTERVAL_SECONDS
    })

    useEffect(() => {
        if (response) {
            refetchResponse(response)
        }
    }, [startedUploadingsCount])

    return {
        places: response === null ? null : response,
        createPermanentPlace: (name: string, address: string) => createPermanentPlace(name, address).then(refetchResponse),
        removePermanentPlace: (placeId: string) => removePermanentPlace(placeId).then(refetchResponse)
    }
}