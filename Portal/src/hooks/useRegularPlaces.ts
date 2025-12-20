import { useEffect } from "react"
import { createPermanentPlace, listRegularPlaces, removePermanentPlace } from "../clients/coreClient.ts"
import { Place } from "../classes/Place.ts"
import type { UseRegularPlacesResult } from "../types/UseRegularPlacesResult.ts"
import type { PlaceIncludedEntity, PlaceSortingStrategy } from "../types/CoreSwaggerTypes.ts"
import { ONE_HOUR_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"
import { useAlbumsBeingUploaded } from "./useAlbumsBeingUploaded.ts"

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
    nearbyPlaces?: number
    limit?: number
    include?: PlaceIncludedEntity[]
    sort?: PlaceSortingStrategy
}

export const useRegularPlaces = ({ tripId, categoryId, labelId, year, albumId, photoId, minStart, maxEnd, nearbyPlaces, limit, include, sort }: UseRegularPlacesProps = {}): UseRegularPlacesResult => {
    const { startedUploadingsCount, isBeingUploaded } = useAlbumsBeingUploaded()

    const { response, refetchResponse } = useQuery({
        queryKey: ["listRegularPlaces", tripId, categoryId, labelId, `${year}`, albumId, photoId, `${minStart - (minStart % RESPONSE_VALIDITY_SECONDS)}`, `${maxEnd - (maxEnd % RESPONSE_VALIDITY_SECONDS)}`, `${nearbyPlaces}`, `${limit}`, ...(include ?? []), sort],
        queryFn: () => listRegularPlaces({ tripId, categoryId, labelId, year, albumId, photoId, minStart, maxEnd, nearbyPlaces, limit, include, sort }),
        staleTime: RESPONSE_VALIDITY_SECONDS * 1000,
        refetchInterval: query => query.state.data?.flatMap(place => place.dates ?? [])?.some(date => (date.album?.uploadingStart && date.album?.uploadingProgress) || isBeingUploaded(date)) && ALBUM_UPLOADING_REFETCH_INTERVAL_SECONDS
    })

    useEffect(() => {
        if (response) {
            refetchResponse()
        }
    }, [startedUploadingsCount])

    return {
        places: response?.map(place => new Place(place)),
        createPermanentPlace: (name: string, address: string) => createPermanentPlace(name, address).then(refetchResponse),
        removePermanentPlace: (placeId: string) => removePermanentPlace(placeId).then(refetchResponse)
    }
}