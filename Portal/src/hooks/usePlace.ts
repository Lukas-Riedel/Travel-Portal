import { useEffect } from "react"

import {
createPlaceHighlight, createPlaceLabel, createPlaceNote, getCoordinates,     getPlace, refreshPlaceAlbum,     refreshPlaceHighlights,
removePlaceHighlight,
removePlaceLabel,
removePlaceNote,
updateHighlightQualityAttributes,     updatePlaceAlbumsReviewed,
    updatePlaceCountry,
    updatePlaceExcerpt, updatePlaceLocation,     updatePlaceMainHighlight, updatePlaceName,     updatePlaceNoteContent} from "../clients/coreClient.ts"
import type { UsePlaceResult } from "../types/UsePlaceResult.ts"
import { useAlbumsBeingUploaded } from "./useAlbumsBeingUploaded.ts"
import { useQuery } from "./useQuery.ts"

const ALBUM_UPLOADING_REFETCH_INTERVAL_SECONDS = 5

export const usePlace = (placeId?: string, nearbyPlaces?: number): UsePlaceResult => {
    const { startedUploadingsCount, isBeingUploaded } = useAlbumsBeingUploaded()

    // Do not use setResponse due to the nearbyPlaces configuration (which is not reflected in the response of the update operations).
    const { response, refetchResponse } = useQuery({
        queryKey: ["getPlace", placeId, `${nearbyPlaces}`],
        queryFn: () => getPlace(placeId!, nearbyPlaces),
        enabled: !!placeId,
        staleTime: 0, // Always refetch in order to display latest highlights and albums.
        refetchInterval: query => query.state.data?.dates?.some(date => (date.album?.uploadingStart && date.album?.uploadingProgress) || isBeingUploaded(date)) && ALBUM_UPLOADING_REFETCH_INTERVAL_SECONDS
    })

    useEffect(() => {
        if (response) {
            refetchResponse(response)
        }
    }, [startedUploadingsCount])

    return {
        place: response ?? null,
        updatePlaceName: (name: string) => updatePlaceName(placeId!, name).then(refetchResponse),
        updatePlaceAddress: (address: string) => getCoordinates(address).then(coordinates => updatePlaceLocation(placeId!, coordinates.latitude, coordinates.longitude)).then(refetchResponse),
        createPlaceHighlight: (photoId: string) => createPlaceHighlight(placeId!, photoId).then(refetchResponse),
        removePlaceHighlight: (highlightId: string) => removePlaceHighlight(placeId!, highlightId).then(refetchResponse),
        updatePlaceMainHighlight: (highlightId: string) => updatePlaceMainHighlight(placeId!, highlightId).then(refetchResponse),
        updatePlaceHighlightQualityAttributes: (highlightId: string, composition: number | null, sky: number | null, shadows: number | null, circumstances: number | null, atmosphere: number | null, impression: number | null) =>
            updateHighlightQualityAttributes(highlightId, composition, sky, shadows, circumstances, atmosphere, impression).then(refetchResponse),
        createPlaceLabel: (name: string) => createPlaceLabel(placeId!, name).then(refetchResponse),
        removePlaceLabel: (labelId: string) => removePlaceLabel(placeId!, labelId).then(refetchResponse),
        updatePlaceExcerpt: (excerpt: string) => updatePlaceExcerpt(placeId!, excerpt).then(refetchResponse),
        refreshPlaceExcerpt: () => updatePlaceExcerpt(placeId!, null).then(refetchResponse),
        updatePlaceLocation: (latitude: number, longitude: number) => updatePlaceLocation(placeId!, latitude, longitude).then(refetchResponse),
        updatePlaceAlbumsReviewed: () => Promise.all((response?.dates?.map(date => date.album)?.filter((album): album is NonNullable<typeof album> => album != null && !album.reviewed) ?? []).map(album => updatePlaceAlbumsReviewed(placeId!, album.id))).then(refetchResponse),
        refreshPlaceAlbum: (albumId: string, mainPhotoPosition?: number, batchId?: string) => refreshPlaceAlbum(placeId!, albumId, { mainPhotoPosition, batchId }).then(refetchResponse),
        createPlaceNote: (name: string) => createPlaceNote(placeId!, name).then(refetchResponse),
        updatePlaceNoteContent: (noteId: string, content: string) => updatePlaceNoteContent(placeId!, noteId, content).then(refetchResponse),
        removePlaceNote: (noteId: string) => removePlaceNote(placeId!, noteId).then(refetchResponse),
        updatePlaceCountry: (country: string) => updatePlaceCountry(placeId!, country).then(refetchResponse),
        refreshPlaceHighlights: (count: number) => refreshPlaceHighlights(placeId!, count).then(refetchResponse)
    }
}