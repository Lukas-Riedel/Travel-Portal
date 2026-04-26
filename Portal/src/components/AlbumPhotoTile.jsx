import { Edit2, SendToBack, Star } from "lucide-react"
import PhotoTile from "./PhotoTile"
import { useState } from "react"
import { getDateString } from "../utils/helpers"
import { useDevices } from "../hooks/useDevices"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"

const agentOnlineStatusThresholdSeconds = 60

export default function AlbumPhotoTile({ place, album, photo, photoPosition, onPhotoReplaced, onMainPhotoUpdated }) {
    const agents = useDevices({ type: "agent" })
    const { showUpdateAlbumMainPhotoToast, showReplacePhotoToast } = usePredefinedUserInput()

    const [overlayType, setOverlayType] = useState(0)

    const handlePhotoReplaced = () => {
        showReplacePhotoToast(agents.filter(agent => agent.lastSeen + agentOnlineStatusThresholdSeconds > Date.now() / 1000),
            (path, agentId, sendNotification) => onPhotoReplaced(agentId, place.id, album.id, place.name, photo.id, path, sendNotification).then(() => window.open(photo.permalink, "_blank")))
    }

    const handleMainPhotoUpdated = () => {
        showUpdateAlbumMainPhotoToast(() => onMainPhotoUpdated(album.id, photoPosition))
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
                    categories={[place.getCategory("mostSpecificWithMetadata")]}
                    firstLineText={place.name}
                    secondLineText={getDateString(Date.now() / 1000)} />
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