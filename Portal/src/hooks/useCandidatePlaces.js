import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import Place from "../model/place"
import { useEffect, useMemo, useState } from "react"

export const useCandidatePlaces = ({ tripId, categoryId, labelName, include, sort } = {}) => {
    const api = useApi()
    const { isAdmin } = useAuth()

    const [currentLocation, setCurrentLocation] = useState(null)

    useEffect(() => {
        navigator.geolocation.getCurrentPosition(location => {
            setCurrentLocation({
                latitude: location.coords.latitude,
                longitude: location.coords.longitude
            })
        })
    }, [])

    const query = useQuery({
        queryKey: ["listCandidatePlaces", tripId, categoryId, labelName, include, sort],
        queryFn: () => api.listCandidatePlaces({ tripId, categoryId, labelName, include, sort }),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 2,
    })

    const refetchCandidatePlaces = _ => query.refetch()

    const candidatePlaces = useMemo(() => query.data && query.data
        .map(place => new Place({ ...place, distance: currentLocation && Math.round(new Place(place).getHaversineDistanceTo(currentLocation)) })), [query, currentLocation])

    return {
        candidatePlaces,
        changeCurrentLocation: setCurrentLocation,
        createCandidatePlace: (name, address) => api.createCandidatePlace(name, address).then(refetchCandidatePlaces),
        removeCandidatePlace: placeId => api.removeCandidatePlace(placeId).then(refetchCandidatePlaces)
    }
}