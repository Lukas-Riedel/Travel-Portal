import { useQuery } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import { listPlaceAlbumPhotos } from "../clients/coreClient"

export const usePlaceAlbumPhotos = (placeId, albumId) => {
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listPlaceAlbumPhotos", placeId, albumId],
        queryFn: () => listPlaceAlbumPhotos(placeId, albumId),
        enabled: !!placeId && !!albumId,
        staleTime: isAdmin ? 0 : 1000 * 60 * 10
    })

    // TODO: Map to Photo objects
    return query.data
}