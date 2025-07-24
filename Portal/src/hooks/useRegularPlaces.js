import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import Place from "../model/place"
import { useEvents } from "./useEvents"
import { useEffect, useMemo } from "react"

export const useRegularPlaces = ({ tripId, categoryId, labelId, year, albumId, photoId, minStart, maxEnd, include, sort } = {}) => {
    const { listRegularPlaces } = useApi()
    const { isAdmin } = useAuth()
    const { events: processingStartedEvents } = useEvents("ProcessingStarted")
    const { events: processingEndedEvents } = useEvents("ProcessingEnded")

    const uploadedAlbumIds = useMemo(() => new Set(processingEndedEvents?.filter(event => event.name === "PhotosUploadingTriggered")?.map(event => event.args.albumId) ?? []), [processingStartedEvents])
    const albumIdsBeingUploaded = useMemo(() => new Set(processingStartedEvents?.filter(event => event.name === "PhotosUploadingTriggered")?.filter(event => !uploadedAlbumIds.has(event.args.albumId))
        ?.map(event => event.args.albumId) ?? []), [uploadedAlbumIds, processingStartedEvents])

    const validity = 60 * 60 * 2
    const query = useQuery({
        queryKey: ["listRegularPlaces", tripId, categoryId, labelId, year, albumId, photoId, minStart - (minStart % validity), maxEnd - (maxEnd % validity), include, sort],
        queryFn: () => listRegularPlaces({ tripId, categoryId, labelId, year, albumId, photoId, minStart, maxEnd, include, sort }),
        staleTime: isAdmin ? 0 : 1000 * validity,
        refetchInterval: query => isAdmin && query.state.data?.flatMap(place => place.dates)?.map(date => date.album)?.filter(Boolean)
            ?.some(album => (album.uploadingStart && album.uploadingProgress) || albumIdsBeingUploaded.has(albumId => albumId == album.id)) && 10000
    })

    useEffect(() => {
        if (query.data) {
            query.refetch()
        }
    }, [processingStartedEvents])

    return query.data && query.data.map(place => new Place(place))
}