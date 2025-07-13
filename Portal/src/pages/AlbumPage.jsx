import { useParams } from "react-router-dom"
import { usePlaceAlbumPhotos } from "../hooks/usePlaceAlbumPhotos"
import AlbumPhotoTileGrid from "../components/AlbumPhotoTileGrid"
import { usePlace } from "../hooks/usePlace"
import { useEvents } from "../hooks/useEvents"
import { useMemo } from "react"
import { useTrip } from "../hooks/useTrip"

export default function AlbumPage() {
    const { placeId, albumId } = useParams()
    const { publishPhotoReplacingTriggeredEvent } = useEvents()

    const { place, createPlaceHighlight, refreshPlaceAlbum } = usePlace(placeId)
    const photos = usePlaceAlbumPhotos(placeId, albumId)

    const date = useMemo(() => place?.getDateByAlbumId(albumId), [place, albumId])
    const { trip, createTripHighlight } = useTrip(date?.trip?.id)

    return (
        <AlbumPhotoTileGrid
            place={place}
            trip={trip}
            album={date?.album}
            photos={photos}
            onPlaceHighlightCreated={createPlaceHighlight}
            onTripHighlightCreated={createTripHighlight}
            onPhotoReplaced={publishPhotoReplacingTriggeredEvent}
            onMainPhotoUpdated={refreshPlaceAlbum} />
    )
}