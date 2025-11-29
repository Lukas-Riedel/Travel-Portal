import { Edit2, SendToBack, Star } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import PhotoTile from "./PhotoTile"
import { useUserInput } from "../hooks/useUserInput.tsx"
import { useState } from "react"
import { getDateString } from "../utils/helpers"
import { useDevices } from "../hooks/useDevices"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"

const agentOnlineStatusThresholdSeconds = 60

export default function AlbumPhotoTile({ place, album, photo, photoPosition, onPhotoReplaced, onMainPhotoUpdated }) {
    const { isAdmin } = useAuth()
    const agents = useDevices({ type: "agent" })
    const { showFormToast } = useUserInput()
    const { showUpdateAlbumMainPhotoToast } = usePredefinedUserInput()

    const [overlayType, setOverlayType] = useState(0)

    const handlePhotoReplaced = () => {
        showFormToast(
            "Zadej cestu k nové fotce:",
            [
                { label: "Cesta", required: true },
                { label: "Agent", required: true, type: "select", options: agents.filter(agent => agent.lastSeen + agentOnlineStatusThresholdSeconds > Date.now() / 1000).map(agent => ({ id: agent.id, name: agent.name })) }
            ],
            async (path, agentId) => onPhotoReplaced(agentId, place.id, album.id, place.name, photo.id, path)
                .then(() => window.open(photo.permalink, "_blank")),
            "Nahrazování fotky bude brzy zahájeno",
            "Při nahrazování fotky došlo k chybě"
        )
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
            {isAdmin && (
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
                    {onMainPhotoUpdated && photoPosition && album.mainPhoto.id !== photo.id && (
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