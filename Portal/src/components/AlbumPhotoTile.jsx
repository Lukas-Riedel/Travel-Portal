import { Edit2, Plus, SendToBack, Star, Trash2 } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import PhotoTile from "./PhotoTile"
import showConfirmToast from "./ConfirmToast"
import showInputToast from "./InputToast"
import showFormToast from "./FormToast"
import { useMemo, useState } from "react"
import { getDateString } from "../utils/helpers"

export default function AlbumPhotoTile({ place, trip, album, photo, photoPosition, onPlaceHighlightCreated, onTripHighlightCreated, onPhotoReplaced, onMainPhotoUpdated }) {
    const { isAdmin } = useAuth()

    const [overlayType, setOverlayType] = useState(0)

    const canPlaceHighlightBeAdded = useMemo(() => onPlaceHighlightCreated && place &&
        !place.highlights?.some(highlight => highlight.photo.id === photo.id), [place, photo])
    const canTripHighlightBeAdded = useMemo(() => onTripHighlightCreated && trip &&
        !trip.highlights?.some(highlight => highlight.photo.id === photo.id), [trip, photo])

    const handleHighlightCreated = () => {
        showFormToast(
            "Zadej vlastníka nového highlightu:",
            [
                {
                    required: true, type: "select", options: [
                        canPlaceHighlightBeAdded && { id: "place", name: `Místo ${place.name}` },
                        canTripHighlightBeAdded && { id: "trip", name: `Výlet ${trip.getFullName()}` }
                    ].filter(Boolean)
                }
            ],
            "Highlight byl úspěšně přidán",
            "Nepodařilo se přidat highlight",
            async (type) => {
                if (type === "place") {
                    return onPlaceHighlightCreated(photo.id)
                }
                else if (type === "trip") {
                    return onTripHighlightCreated(photo.id)
                }
                return Promise.reject(`Unknown highlight type '${type}'.`)
            }
        )
    }

    const handlePhotoReplaced = () => {
        showInputToast("Zadej cestu k nové fotce:",
            "",
            "Nahrazování fotky bude brzy zahájeno",
            "Při nahrazování fotky došlo k chybě",
            async (path) => onPhotoReplaced(place.id, place.name, album.id, photo.id, path)
                .then(() => window.open(photo.permalink, "_blank"))
        )
    }

    const handleMainPhotoUpdated = () => {
        showConfirmToast("Opravdu chceš nastavit tuto fotku jako hlavní fotku alba?",
            "Hlavní fotka byla úspěšně nastavena",
            "Nepodařilo se nastavit hlavní fotku",
            async () => onMainPhotoUpdated(album.id, photoPosition)
        )
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
                    firstLineText={place.name} />
            )}
            {overlayType === 2 && (
                <PhotoTile
                    src={photo.url + "=w350-h233"}
                    to={photo.permalink}
                    categories={[place.getCategory("mostSpecificWithMetadata")]}
                    firstLineText={place.name}
                    secondLineText={getDateString(Date.now() / 1000)} />
            )}
            {isAdmin && (
                <div className="flex justify-center gap-2 mt-2">
                    {(canPlaceHighlightBeAdded || canTripHighlightBeAdded) && (
                        <button
                            onClick={handleHighlightCreated}
                            className="btn-large-gray">
                            <Plus size={16} />
                        </button>
                    )}
                    <button
                        onClick={handlePhotoReplaced}
                        className="btn-large-gray">
                        <Edit2 size={16} />
                    </button>
                    {onPhotoReplaced && (
                        <button
                            onClick={() => setOverlayType(prev => (prev + 1) % 3)}
                            className="btn-large-gray">
                            <SendToBack size={16} />
                        </button>
                    )}
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