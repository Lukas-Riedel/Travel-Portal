import { useQuery } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import Place from "../model/place"
import { useEffect, useMemo, useState } from "react"
import { useLocation } from "../contexts/LocationContext"
import { listCandidatePlaces, createCandidatePlace, removeCandidatePlace } from "../clients/coreClient"

export const useCandidatePlaces = ({ tripId, categoryId, labelId, include, sort } = {}) => {
    const resolvedLocation = useLocation()
    const { isAdmin } = useAuth()

    const [currentLocation, setCurrentLocation] = useState(resolvedLocation)

    useEffect(() => {
        if (!currentLocation) {
            setCurrentLocation(resolvedLocation)
        }
    }, [resolvedLocation])

    const query = useQuery({
        queryKey: ["listCandidatePlaces", tripId, categoryId, labelId, include, sort],
        queryFn: () => listCandidatePlaces({ tripId, categoryId, labelId, include, sort }),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 2
    })

    const refetchCandidatePlaces = _ => query.refetch()

    const candidatePlaces = useMemo(() => query.data && query.data
        .map(place => new Place({ ...place, distance: currentLocation && Math.round(new Place(place).getHaversineDistanceTo(currentLocation)) })), [query, currentLocation])

    return {
        candidatePlaces,
        changeCurrentLocation: setCurrentLocation,
        createCandidatePlace: (name, address) => createCandidatePlace(name, address).then(refetchCandidatePlaces),
        removeCandidatePlace: placeId => removeCandidatePlace(placeId).then(refetchCandidatePlaces)
    }
}