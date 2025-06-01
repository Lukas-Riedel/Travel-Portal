import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"

export const usePlaceAlbumPhotos = (placeId, albumId) => {
    const api = useApi()
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listPlaceAlbumPhotos", placeId, albumId],
        queryFn: () => api.listPlaceAlbumPhotos(placeId, albumId),
        staleTime: isAdmin() ? 0 : 1000 * 60 * 10,
    })
    
    // TODO: Map to Photo objects
    return query.data ?? []
}