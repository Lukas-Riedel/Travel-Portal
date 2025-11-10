import { listPlaceAlbumPhotos } from "../clients/coreClient.ts"
import type { UsePlaceAlbumPhotosResult } from "../types/UsePlaceAlbumPhotosResult.ts"
import { ONE_MINUTE_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const usePlaceAlbumPhotos = (placeId?: string, albumId?: string): UsePlaceAlbumPhotosResult => {
    const { response } = useQuery({
        queryKey: ["listPlaceAlbumPhotos", placeId, albumId],
        queryFn: () => listPlaceAlbumPhotos(placeId, albumId),
        enabled: !!placeId && !!albumId,
        staleTime: ONE_MINUTE_SECONDS * 1000
    })

    return response
}