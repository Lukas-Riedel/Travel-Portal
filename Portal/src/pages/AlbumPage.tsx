import { useParams } from "react-router-dom"
import { usePlaceAlbumPhotos } from "../hooks/usePlaceAlbumPhotos.ts"
import AlbumPhotoTileGrid from "../components/AlbumPhotoTileGrid.tsx"
import { usePlace } from "../hooks/usePlace.ts"
import { useEvents } from "../hooks/useEvents.ts"
import { useMemo } from "react"
import { useAuth } from "../contexts/AuthContext.tsx"
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