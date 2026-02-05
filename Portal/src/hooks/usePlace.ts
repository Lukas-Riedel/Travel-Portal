import {
    getPlace, updatePlaceName, getCoordinates, updatePlaceLocation, removePlaceHighlight,
    updatePlaceMainHighlight, updateHighlightQualityAttributes, createPlaceLabel, removePlaceLabel,
    updatePlaceExcerpt, refreshPlaceAlbum, createPlaceHighlight, createPlaceNote, removePlaceNote,
    updatePlaceAlbumsReviewed,
    updatePlaceNoteContent,
    updatePlaceCountry
} from "../clients/coreClient.ts"
import { useEffect } from "react"
import { Place } from "../classes/Place.ts"
import { useQuery } from "./useQuery.ts"
import { useAlbumsBeingUploaded } from "./useAlbumsBeingUploaded.ts"
import { ONE_HOUR_SECONDS } from "../utils/timeUtils.ts"
import type { UsePlaceResult } from "../types/UsePlaceResult.ts"

const ALBUM_UPLOADING_REFETCH_INTERVAL_SECONDS = 5

export const usePlace = (placeId?: string, nearbyPlaces?: number): UsePlaceResult => {
    const { startedUploadingsCount, isBeingUploaded } = useAlbumsBeingUploaded()

    const { response, setResponse, refetchResponse } = useQuery({
        queryKey: ["getPlace", placeId, `${nearbyPlaces}`],
        queryFn: () => getPlace(placeId, nearbyPlaces),
        enabled: !!placeId,
        staleTime: ONE_HOUR_SECONDS * 1000,
        refetchInterval: query => query.state.data?.dates?.some(date => (date.album?.uploadingStart && date.album?.uploadingProgress) || isBeingUploaded(date)) && ALBUM_UPLOADING_REFETCH_INTERVAL_SECONDS
    })

    useEffect(() => {
        if (response) {
            refetchResponse(response)
        }
    }, [startedUploadingsCount])

    return {
        place: response && new Place(response),
        updatePlaceName: (name: string) => updatePlaceName(placeId, name).then(setResponse),
        updatePlaceAddress: (address: string) => getCoordinates(address).then(coordinates => updatePlaceLocation(placeId, coordinates.latitude, coordinates.longitude)).then(setResponse),
        createPlaceHighlight: (photoId: string) => createPlaceHighlight(placeId, photoId).then(refetchResponse),
        removePlaceHighlight: (highlightId: string) => removePlaceHighlight(placeId, highlightId).then(refetchResponse),
        updatePlaceMainHighlight: (highlightId: string) => updatePlaceMainHighlight(placeId, highlightId).then(setResponse),
        updatePlaceHighlightQualityAttributes: (highlightId: string, composition?: number, sky?: number, shadows?: number, circumstances?: number, atmosphere?: number) =>
            updateHighlightQualityAttributes(highlightId, composition, sky, shadows, circumstances, atmosphere).then(refetchResponse),
        createPlaceLabel: (name: string) => createPlaceLabel(placeId, name).then(refetchResponse),
        removePlaceLabel: (labelId: string) => removePlaceLabel(placeId, labelId).then(refetchResponse),
        updatePlaceExcerpt: (excerpt: string) => updatePlaceExcerpt(placeId, excerpt).then(setResponse),
        refreshPlaceExcerpt: () => updatePlaceExcerpt(placeId, null).then(setResponse),
        updatePlaceLocation: (latitude: number, longitude: number) => updatePlaceLocation(placeId, latitude, longitude).then(setResponse),
        updatePlaceAlbumsReviewed: () => Promise.all(response?.dates?.map(date => date.album)?.filter(Boolean)?.filter(album => !album.reviewed)?.map(album => updatePlaceAlbumsReviewed(placeId, album.id))).then(refetchResponse),
        refreshPlaceAlbum: (albumId: string, mainPhotoPosition?: number) => refreshPlaceAlbum(placeId, albumId, mainPhotoPosition ? { mainPhotoPosition } : {}).then(refetchResponse),
        createPlaceNote: (name: string) => createPlaceNote(placeId, name).then(refetchResponse),
        updatePlaceNoteContent: (noteId: string, content: string) => updatePlaceNoteContent(placeId, noteId, content).then(refetchResponse),
        removePlaceNote: (noteId: string) => removePlaceNote(placeId, noteId).then(refetchResponse),
        updatePlaceCountry: (country: string) => updatePlaceCountry(placeId, country).then(setResponse)
    }
}