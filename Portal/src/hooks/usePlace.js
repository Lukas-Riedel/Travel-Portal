import { useQuery, useQueryClient } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import Place from "../model/place"

export const usePlace = (placeId) => {
    const api = useApi()
    const { isAdmin } = useAuth()

    const queryClient = useQueryClient()

    const query = useQuery({
        queryKey: ["getPlace", placeId],
        queryFn: () => api.getPlace(placeId),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 2,
    })

    const setPlace = place => queryClient.setQueryData(["getPlace", placeId], place)
    const refetchPlace = _ => query.refetch()

    return {
        place: query.data && new Place(query.data),
        updatePlaceName: name => api.updatePlaceName(placeId, name).then(setPlace),
        updatePlaceAddress: address => api.getCoordinates(address).then(coordinates => api.updatePlaceLocation(placeId, coordinates.latitude, coordinates.longitude)).then(setPlace),
        removePlaceHighlight: highlightId => api.removePlaceHighlight(placeId, highlightId).then(refetchPlace),
        updatePlaceMainHighlight: highlightId => api.updatePlaceMainHighlight(placeId, highlightId).then(setPlace),
        createPlaceLabel: name => api.createPlaceLabel(placeId, name).then(refetchPlace),
        removePlaceLabel: labelId => api.removePlaceLabel(placeId, labelId).then(refetchPlace),
        updatePlaceExcerpt: excerpt => api.updatePlaceExcerpt(placeId, excerpt).then(setPlace),
        refreshPlaceExcerpt: () => api.updatePlaceExcerpt(placeId, null).then(setPlace),
        updatePlaceLocation: (latitude, longitude) => api.updatePlaceLocation(placeId, latitude, longitude).then(setPlace),
        refreshPlaceAlbum: (albumId, mainPhotoPosition) => api.refreshPlaceAlbum(placeId, albumId, mainPhotoPosition).then(refetchPlace)
    }
}