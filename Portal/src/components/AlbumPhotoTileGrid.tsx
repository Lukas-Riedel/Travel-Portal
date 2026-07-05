import TileGrid from "./TileGrid.jsx"
import AlbumPhotoTile from "./AlbumPhotoTile.tsx"
import type { Place } from "../classes/Place.ts"
import type { Album, Photo } from "../types/CoreSwaggerTypes.ts"

interface AlbumPhotoTileGridProps {
    place: Place | null
    album: Album | null
    photos: Photo[] | null
    photoPosition?: number
    onPhotoReplaced?: (agentId: string, placeId: string, albumId: string, placeName: string, photoId: string, path: string, sendNotification: boolean) => Promise<void>
    onMainPhotoUpdated?: (albumId: string, photoPosition: number) => Promise<Album>
}

export default function AlbumPhotoTileGrid({ place, album, photos, onPhotoReplaced, onMainPhotoUpdated }: AlbumPhotoTileGridProps) {
    return (
        <TileGrid>
            {photos?.map((photo, index) => (
                <AlbumPhotoTile
                    key={photo.id}
                    place={place}
                    album={album}
                    photo={photo}
                    photoPosition={index + 1}
                    onPhotoReplaced={onPhotoReplaced}
                    onMainPhotoUpdated={onMainPhotoUpdated} />
            ))}
        </TileGrid>
    )
}