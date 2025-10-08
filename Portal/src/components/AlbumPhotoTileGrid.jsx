import TileGrid from "./TileGrid.jsx"
import AlbumPhotoTile from "./AlbumPhotoTile.jsx"

export default function AlbumPhotoTileGrid({ place, album, photos, onPhotoReplaced, onMainPhotoUpdated }) {
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