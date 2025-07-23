import { useQuery, useQueryClient } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import Place from "../model/place"
import { useEffect, useMemo } from "react"
import { useEvents } from "./useEvents"

export const usePlace = (placeId) => {
    const { getPlace, updatePlaceName, getCoordinates, updatePlaceLocation, removePlaceHighlight,
        updatePlaceMainHighlight, updateHighlightQualityAttributes, createPlaceLabel, removePlaceLabel,
        updatePlaceExcerpt, refreshPlaceAlbum, createPlaceHighlight } = useApi()
    const { isAdmin } = useAuth()
    const { events: photosUploadingStartedEvents } = useEvents("PhotosUploadingStarted")
    const { events: photosUploadingEndedEvents } = useEvents("PhotosUploadingEnded")

    const uploadedAlbumIds = useMemo(() => new Set(photosUploadingEndedEvents?.map(message => message.albumId) ?? []), [photosUploadingStartedEvents])
    const albumIdsBeingUploaded = useMemo(() => new Set(photosUploadingStartedEvents?.filter(message => !uploadedAlbumIds.has(message.albumId))
        ?.map(message => message.albumId) ?? []), [uploadedAlbumIds, photosUploadingStartedEvents])

    const queryClient = useQueryClient()

    const query = useQuery({
        queryKey: ["getPlace", placeId],
        queryFn: () => getPlace(placeId),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 2,
        refetchInterval: query => isAdmin && query.state.data?.dates?.map(date => date.album)?.filter(Boolean)
            ?.some(album => (album.uploadingStart && album.uploadingProgress) || albumIdsBeingUploaded.has(albumId => albumId == album.id)) && 10000
    })

    const setPlace = place => queryClient.setQueryData(["getPlace", placeId], place)
    const refetchPlace = _ => query.refetch()

    useEffect(() => {
        if (query.data) {
            query.refetch()
        }
    }, [photosUploadingStartedEvents])

    return {
        place: query.data && new Place(query.data),
        updatePlaceName: name => updatePlaceName(placeId, name).then(setPlace),
        updatePlaceAddress: address => getCoordinates(address).then(coordinates => updatePlaceLocation(placeId, coordinates.latitude, coordinates.longitude)).then(setPlace),
        createPlaceHighlight: photoId => createPlaceHighlight(placeId, photoId).then(refetchPlace),
        removePlaceHighlight: highlightId => removePlaceHighlight(placeId, highlightId).then(refetchPlace),
        updatePlaceMainHighlight: highlightId => updatePlaceMainHighlight(placeId, highlightId).then(setPlace),
        updatePlaceHighlightQualityAttributes: (highlightId, composition, sky, shadows, circumstances, atmosphere) =>
            updateHighlightQualityAttributes(highlightId, composition, sky, shadows, circumstances, atmosphere).then(refetchPlace),
        createPlaceLabel: name => createPlaceLabel(placeId, name).then(refetchPlace),
        removePlaceLabel: labelId => removePlaceLabel(placeId, labelId).then(refetchPlace),
        updatePlaceExcerpt: excerpt => updatePlaceExcerpt(placeId, excerpt).then(setPlace),
        refreshPlaceExcerpt: () => updatePlaceExcerpt(placeId, null).then(setPlace),
        updatePlaceLocation: (latitude, longitude) => updatePlaceLocation(placeId, latitude, longitude).then(setPlace),
        refreshPlaceAlbum: (albumId, mainPhotoPosition) => refreshPlaceAlbum(placeId, albumId, { mainPhotoPosition }).then(refetchPlace)
    }
}