import { useEffect, useMemo, useState } from "react"
import { useLocation } from "../contexts/LocationContext.jsx"
import { listCandidatePlaces, createCandidatePlace, removeCandidatePlace } from "../clients/coreClient.ts"
import type { PlaceIncludedEntity, PlaceSortingStrategy } from "../types/CoreSwaggerTypes.ts"
import type { UseCandidatePlacesResult } from "../types/UseCandidatePlacesResult.ts"
import { useQuery } from "./useQuery.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { DistanceAwarePlace } from "../classes/DistanceAwarePlace.ts"
import { getHaversineDistance } from "../utils/geocodingUtils.ts"

interface UseCandidatePlacesProps {
    tripId?: string
    categoryId?: string
    labelId?: string
    include?: PlaceIncludedEntity[]
    sort?: PlaceSortingStrategy
}

export const useCandidatePlaces = ({ tripId, categoryId, labelId, include, sort }: UseCandidatePlacesProps = {}): UseCandidatePlacesResult => {
    const resolvedLocation = useLocation()

    const [currentLocation, setCurrentLocation] = useState(resolvedLocation)

    useEffect(() => {
        if (!currentLocation) {
            setCurrentLocation(resolvedLocation)
        }
    }, [resolvedLocation])

    const { response, refetchResponse } = useQuery({
        queryKey: ["listCandidatePlaces", tripId, categoryId, labelId, ...(include ?? []), sort],
        queryFn: () => listCandidatePlaces({ tripId, categoryId, labelId, include, sort }),
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return {
        candidatePlaces: useMemo(() => response?.map(place => new DistanceAwarePlace(place, currentLocation && getHaversineDistance(place, currentLocation))), [response, currentLocation]),
        changeCurrentLocation: setCurrentLocation,
        createCandidatePlace: (name: string, address: string) => createCandidatePlace(name, address).then(refetchResponse),
        removeCandidatePlace: (placeId: string) => removeCandidatePlace(placeId).then(refetchResponse)
    }
}