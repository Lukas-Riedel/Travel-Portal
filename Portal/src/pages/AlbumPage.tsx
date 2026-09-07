import { useMemo } from "react"
import { useParams } from "react-router-dom"

import AlbumPhotoTileGrid from "../components/AlbumPhotoTileGrid.tsx"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useEvents } from "../hooks/useEvents.ts"
import { usePlace } from "../hooks/usePlace.ts"
import { usePlaceAlbumPhotos } from "../hooks/usePlaceAlbumPhotos.ts"
import { UserRole } from "../types/CoreSwaggerTypes.ts"

export default function AlbumPage() {
    const { placeId, albumId } = useParams()
    const { hasRole } = useAuth()
    const { publishPhotoReplacingTriggeredEvent } = useEvents()

    const { place, refreshPlaceAlbum } = usePlace(placeId)
    const photos = usePlaceAlbumPhotos(placeId, albumId)

    const date = useMemo(() => place?.getDateByAlbumId(albumId), [place, albumId])

    return hasRole(UserRole.PlaceAlbumRead) && (
        <AlbumPhotoTileGrid
            place={place}
            album={date?.album}
            photos={photos}
            onPhotoReplaced={hasRole(UserRole.PlaceAlbumEdit) && publishPhotoReplacingTriggeredEvent}
            onMainPhotoUpdated={hasRole(UserRole.PlaceAlbumEdit) && refreshPlaceAlbum} />
    )
}