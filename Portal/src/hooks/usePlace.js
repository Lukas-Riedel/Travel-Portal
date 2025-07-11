import { useQuery, useQueryClient } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import Place from "../model/place"
import { useEffect, useMemo } from "react"
import { useEvents } from "./useEvents"

export const usePlace = (placeId) => {
    const { getPlace, updatePlaceName, getCoordinates, updatePlaceLocation, removePlaceHighlight,
        updatePlaceMainHighlight, updateHighlightQualityAttributes, createPlaceLabel, removePlaceLabel,
        updatePlaceExcerpt, refreshPlaceAlbum } = useApi()
    const { isAdmin } = useAuth()
    const events = useEvents("FirstPhotoUploaded")

    const albumIdsBeingUploaded = useMemo(() => events?.map(message => message.albumId), [events])

    const queryClient = useQueryClient()

    const query = useQuery({
        queryKey: ["getPlace", placeId],
        queryFn: () => getPlace(placeId),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 2,
        refetchInterval: query => isAdmin && query.state.data?.dates?.map(date => date.album)?.filter(Boolean)
            ?.some(album => (album.uploadingStart && album.uploadingProgress) || albumIdsBeingUploaded.some(albumIdBeingUploaded => albumIdBeingUploaded == album.id)) && 2000
    })

    const setPlace = place => queryClient.setQueryData(["getPlace", placeId], place)
    const refetchPlace = _ => query.refetch()

    useEffect(() => {
        if (query.data) {
            query.refetch()
        }
    }, [events])

    return {
        place: query.data && new Place(query.data),
        updatePlaceName: name => updatePlaceName(placeId, name).then(setPlace),
        updatePlaceAddress: address => getCoordinates(address).then(coordinates => updatePlaceLocation(placeId, coordinates.latitude, coordinates.longitude)).then(setPlace),
        removePlaceHighlight: highlightId => removePlaceHighlight(placeId, highlightId).then(refetchPlace),
        updatePlaceMainHighlight: highlightId => updatePlaceMainHighlight(placeId, highlightId).then(setPlace),
        updatePlaceHighlightQualityAttributes: (highlightId, composition, sky, shadows, circumstances) =>
            updateHighlightQualityAttributes(highlightId, composition, sky, shadows, circumstances).then(refetchPlace),
        createPlaceLabel: name => createPlaceLabel(placeId, name).then(refetchPlace),
        removePlaceLabel: labelId => removePlaceLabel(placeId, labelId).then(refetchPlace),
        updatePlaceExcerpt: excerpt => updatePlaceExcerpt(placeId, excerpt).then(setPlace),
        refreshPlaceExcerpt: () => updatePlaceExcerpt(placeId, null).then(setPlace),
        updatePlaceLocation: (latitude, longitude) => updatePlaceLocation(placeId, latitude, longitude).then(setPlace),
        refreshPlaceAlbum: (albumId, mainPhotoPosition) => refreshPlaceAlbum(placeId, albumId, mainPhotoPosition).then(refetchPlace)
    }
}