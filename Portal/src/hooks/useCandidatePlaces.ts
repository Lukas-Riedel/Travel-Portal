import { useEffect, useState } from "react"

import { DistanceAwarePlace } from "../classes/DistanceAwarePlace.ts"
import { createCandidatePlace, listCandidatePlaces, removeCandidatePlace } from "../clients/coreClient.ts"
import { useLocation } from "../contexts/LocationContext.jsx"
import type { PlaceIncludedEntity, PlaceSortingStrategy } from "../types/CoreSwaggerTypes.ts"
import type { UseCandidatePlacesResult } from "../types/UseCandidatePlacesResult.ts"
import { getHaversineDistance } from "../utils/geocodingUtils.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

interface UseCandidatePlacesProps {
    tripId?: string
    categoryId?: string
    labelId?: string
    nearbyPlaces?: number
    include?: PlaceIncludedEntity[]
    sort?: PlaceSortingStrategy
}

export const useCandidatePlaces = ({ tripId, categoryId, labelId, nearbyPlaces, include, sort }: UseCandidatePlacesProps = {}): UseCandidatePlacesResult => {
    const resolvedLocation = useLocation()

    const [currentLocation, setCurrentLocation] = useState(resolvedLocation)

    useEffect(() => {
        if (!currentLocation) {
            setCurrentLocation(resolvedLocation)
        }
    }, [resolvedLocation])

    const { response, refetchResponse } = useQuery({
        queryKey: ["listCandidatePlaces", tripId, categoryId, labelId, `${nearbyPlaces}`, ...(include ?? []), sort ?? ""],
        queryFn: () => listCandidatePlaces({ tripId, categoryId, labelId, nearbyPlaces, include, sort }),
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return {
        candidatePlaces: response === null ? null : response.map(place => new DistanceAwarePlace(place, currentLocation ? getHaversineDistance(place, currentLocation) : undefined)),
        changeCurrentLocation: setCurrentLocation,
        createCandidatePlace: (name: string, address: string) => createCandidatePlace(name, address).then(refetchResponse),
        removeCandidatePlace: (placeId: string) => removeCandidatePlace(placeId).then(refetchResponse)
    }
}