import { useParams } from "react-router-dom"
import { usePlaceAlbumPhotos } from "../hooks/usePlaceAlbumPhotos"
import AlbumPhotoTileGrid from "../components/AlbumPhotoTileGrid"
import { usePlace } from "../hooks/usePlace"
import { useEvents } from "../hooks/useEvents"
import { useMemo } from "react"

export default function AlbumPage() {
    const { placeId, albumId } = useParams()
    const { publishPhotoReplacingTriggeredEvent } = useEvents()

    const { place, refreshPlaceAlbum } = usePlace(placeId)
    const photos = usePlaceAlbumPhotos(placeId, albumId)

    const date = useMemo(() => place?.getDateByAlbumId(albumId), [place, albumId])

    return (
        <AlbumPhotoTileGrid
            place={place}
            album={date?.album}
            photos={photos}
            onPhotoReplaced={publishPhotoReplacingTriggeredEvent}
            onMainPhotoUpdated={refreshPlaceAlbum} />
    )
}