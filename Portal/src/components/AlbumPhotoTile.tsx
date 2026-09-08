import { Edit2, SendToBack, Star } from "lucide-react"
import { useState } from "react"
import { useTranslation } from "react-i18next"

import type { Place } from "../classes/Place.ts"
import { useOnlineAgents } from "../hooks/useOnlineAgents.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { type Album, type Photo } from "../types/CoreSwaggerTypes.ts"
import { InternalCategoryCategory } from "../types/InternalCategoryCategory.ts"
import { formatTimestamp, getCurrentTimestamp } from "../utils/timeUtils.ts"
import PhotoTile from "./PhotoTile.tsx"

interface AlbumPhotoTileProps {
    place: Place | null
    album: Album | null
    photo: Photo | null
    photoPosition?: number
    onPhotoReplaced?: (agentId: string, placeId: string, albumId: string, placeName: string, photoId: string, path: string, sendNotification: boolean) => Promise<void>
    onMainPhotoUpdated?: (albumId: string, photoPosition: number) => Promise<Album>
}

export default function AlbumPhotoTile({ place, album, photo, photoPosition, onPhotoReplaced, onMainPhotoUpdated }: AlbumPhotoTileProps) {
    const { t } = useTranslation()
    const onlineAgents = useOnlineAgents()
    const { showUpdateAlbumMainPhotoToast, showReplacePhotoToast } = usePredefinedUserInput()

    const [overlayType, setOverlayType] = useState(0)

    const handlePhotoReplaced = () => {
        if (onlineAgents && place && album && photo && onPhotoReplaced) {
            showReplacePhotoToast(onlineAgents,
                (path, agentId, sendNotification) => onPhotoReplaced(agentId, place.id, album.id, place.name, photo.id, path, sendNotification)
                    .then(() => {
                        window.open(photo.permalink, "_blank")
                    }))
        }
    }

    const handleMainPhotoUpdated = () => {
        if (onMainPhotoUpdated && album && photoPosition) {
            showUpdateAlbumMainPhotoToast(() => onMainPhotoUpdated(album.id, photoPosition))
        }
    }

    return (
        <div>
            {overlayType === 0 && (
                <PhotoTile
                    // TODO: Create a class with the method to obtain the thumbnail URL.
                    src={photo ? (photo.url + "=w350-h233") : null}
                    to={photo?.permalink} />
            )}
            {overlayType === 1 && place && (
                <PhotoTile
                    // TODO: Create a class with the method to obtain the thumbnail URL.
                    src={photo ? (photo.url + "=w350-h233") : null}
                    to={photo?.permalink}
                    categories={place.getCategory(InternalCategoryCategory.MostSpecificWithMetadata) ? [place.getCategory(InternalCategoryCategory.MostSpecificWithMetadata)!] : undefined}
                    firstLineText={place.name}
                    secondLineText={formatTimestamp(getCurrentTimestamp(), t("general.format.date.year.included"))} />
            )}
            {(onPhotoReplaced || onMainPhotoUpdated) && (
                <div className="flex justify-center gap-2 mt-2">
                    {onPhotoReplaced && (
                        <button
                            onClick={handlePhotoReplaced}
                            className="btn-pill">
                            <Edit2 size={16} />
                        </button>
                    )}
                    <button
                        onClick={() => setOverlayType(prev => (prev + 1) % 2)}
                        className="btn-pill">
                        <SendToBack size={16} />
                    </button>
                    {onMainPhotoUpdated && photoPosition && album?.mainPhoto?.id !== photo?.id && photo != null && (
                        <button
                            onClick={handleMainPhotoUpdated}
                            className="btn-pill">
                            <Star size={16} />
                        </button>
                    )}
                </div>
            )}
        </div>
    )
}