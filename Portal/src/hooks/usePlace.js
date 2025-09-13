import { useQuery, useQueryClient } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import Place from "../model/place"
import { useEffect, useMemo } from "react"
import { useEvents } from "./useEvents"

export const usePlace = placeId => {
    const { getPlace, updatePlaceName, getCoordinates, updatePlaceLocation, removePlaceHighlight,
        updatePlaceMainHighlight, updateHighlightQualityAttributes, createPlaceLabel, removePlaceLabel,
        updatePlaceExcerpt, refreshPlaceAlbum, createPlaceHighlight, createPlaceNote, removePlaceNote } = useApi()
    const { isAdmin } = useAuth()
    const { events: processingStartedEvents } = useEvents("ProcessingStarted")
    const { events: processingEndedEvents } = useEvents("ProcessingEnded")
    const { events: processingFailedEvents } = useEvents("ProcessingFailed")

    const uploadedDates = useMemo(() => new Set([...(processingEndedEvents ?? []), ...(processingFailedEvents ?? [])].filter(event => event.name === "PhotosUploadingTriggered").map(event => event.args.timestamp)), [processingEndedEvents, processingFailedEvents])
    // TODO: This won't work for repeated uploads for the same date.
    const datesBeingUploaded = useMemo(() => new Set(processingStartedEvents?.filter(event => event.name === "PhotosUploadingTriggered")?.filter(event => !uploadedDates.has(event.args.timestamp))
        ?.map(event => event.args.timestamp) ?? []), [uploadedDates, processingStartedEvents])

    const queryClient = useQueryClient()

    const query = useQuery({
        queryKey: ["getPlace", placeId],
        queryFn: () => getPlace(placeId),
        enabled: !!placeId,
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 2,
        refetchInterval: query => isAdmin && query.state.data?.dates?.some(date => (date.album?.uploadingStart && date.album?.uploadingProgress) || datesBeingUploaded.has(date.start)) && 10000
    })

    const setPlace = place => queryClient.setQueryData(["getPlace", placeId], place)
    const refetchPlace = _ => query.refetch()

    useEffect(() => {
        if (query.data) {
            query.refetch()
        }
    }, [processingStartedEvents])

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
        refreshPlaceAlbum: (albumId, mainPhotoPosition) => refreshPlaceAlbum(placeId, albumId, { mainPhotoPosition }).then(refetchPlace),
        createPlaceNote: name => createPlaceNote(placeId, name).then(refetchPlace),
        removePlaceNote: noteId => removePlaceNote(placeId, noteId).then(refetchPlace)
    }
}