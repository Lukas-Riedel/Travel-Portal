import { Edit2, SendToBack, Star } from "lucide-react"
import PhotoTile from "./PhotoTile.tsx"
import { useState } from "react"
import { useDevices } from "../hooks/useDevices.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { isDeviceOnline } from "../utils/deviceUtils.ts"
import type { Place } from "../classes/Place.ts"
import { DeviceType, type Album, type Photo } from "../types/CoreSwaggerTypes.ts"
import { useTranslation } from "react-i18next"
import { InternalCategoryCategory } from "../types/InternalCategoryCategory.ts"
import { formatTimestamp, getCurrentTimestamp } from "../utils/timeUtils.ts"

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
    const agents = useDevices({ type: DeviceType.Agent })
    const { showUpdateAlbumMainPhotoToast, showReplacePhotoToast } = usePredefinedUserInput()

    const [overlayType, setOverlayType] = useState(0)

    const handlePhotoReplaced = () => {
        if (agents && place && album && photo && onPhotoReplaced) {
            showReplacePhotoToast(agents.filter(agent => isDeviceOnline(agent)),
                (path, agentId, sendNotification) => onPhotoReplaced(agentId, place.id, album.id, place.name, photo.id, path, sendNotification)
                    .then(() => {
                        window.open(photo.permalink, "_blank")
                    }))
        }
    }

    const handleMainPhotoUpdated = () => {
        if (onMainPhotoUpdated && photoPosition) {
            showUpdateAlbumMainPhotoToast(() => onMainPhotoUpdated(album.id, photoPosition))
        }
    }

    return (
        <div>
            {overlayType === 0 && (
                <PhotoTile
                    src={photo.url + "=w350-h233"}
                    to={photo.permalink} />
            )}
            {overlayType === 1 && (
                <PhotoTile
                    src={photo.url + "=w350-h233"}
                    to={photo.permalink}
                    categories={[place.getCategory(InternalCategoryCategory.MostSpecificWithMetadata)]}
                    firstLineText={place.name}
                    secondLineText={formatTimestamp(getCurrentTimestamp(), t("general.format.date.year.included"))} />
            )}
            {(onPhotoReplaced || onMainPhotoUpdated) && (
                <div className="flex justify-center gap-2 mt-2">
                    {onPhotoReplaced && (
                        <button
                            onClick={handlePhotoReplaced}
                            className="btn-large-gray">
                            <Edit2 size={16} />
                        </button>
                    )}
                    <button
                        onClick={() => setOverlayType(prev => (prev + 1) % 2)}
                        className="btn-large-gray">
                        <SendToBack size={16} />
                    </button>
                    {onMainPhotoUpdated && photoPosition && album?.mainPhoto?.id !== photo.id && (
                        <button
                            onClick={handleMainPhotoUpdated}
                            className="btn-large-gray">
                            <Star size={16} />
                        </button>
                    )}
                </div>
            )}
        </div>
    )
}