import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import Place from "../model/place"
import { useEvents } from "./useEvents"
import { useEffect, useMemo } from "react"

export const useRegularPlaces = ({ tripId, categoryId, labelName, year, minStart, maxEnd, include, sort } = {}) => {
    const api = useApi()
    const { isAdmin } = useAuth()
    const events = useEvents("FirstPhotoUploaded")

    const albumIdsBeingUploaded = useMemo(() => events?.map(message => message.albumId), [events])

    const validity = 60 * 60 * 2
    const query = useQuery({
        queryKey: ["listRegularPlaces", tripId, categoryId, labelName, year, minStart - (minStart % validity), maxEnd - (maxEnd % validity), include, sort],
        queryFn: () => api.listRegularPlaces({ tripId, categoryId, labelName, year, minStart, maxEnd, include, sort }),
        staleTime: isAdmin ? 0 : 1000 * validity,
        refetchInterval: query => isAdmin && query.state.data?.flatMap(place => place.dates)?.map(date => date.album)?.filter(Boolean)
            ?.some(album => (album.uploadingStart && album.uploadingProgress) || albumIdsBeingUploaded.some(albumIdBeingUploaded => albumIdBeingUploaded == album.id)) && 2000
    })

    useEffect(() => {
        if (query.data) {
            query.refetch()
        }
    }, [events])

    return query.data && query.data.map(place => new Place(place))
}