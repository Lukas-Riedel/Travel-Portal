import TileGrid from "./TileGrid.jsx"
import AlbumPhotoTile from "./AlbumPhotoTile.jsx"

export default function AlbumPhotoTileGrid({ place, trip, album, photos, onPlaceHighlightCreated, onTripHighlightCreated, onPhotoReplaced, onMainPhotoUpdated }) {
    return (
        <TileGrid>
            {photos?.map((photo, index) => (
                <AlbumPhotoTile
                    key={photo.id}
                    place={place}
                    trip={trip}
                    album={album}
                    photo={photo}
                    photoPosition={index + 1}
                    onPlaceHighlightCreated={onPlaceHighlightCreated}
                    onTripHighlightCreated={onTripHighlightCreated}
                    onPhotoReplaced={onPhotoReplaced}
                    onMainPhotoUpdated={onMainPhotoUpdated} />
            ))}
        </TileGrid>
    )
}