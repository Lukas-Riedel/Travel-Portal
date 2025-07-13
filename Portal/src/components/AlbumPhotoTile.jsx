import { Edit2, Plus, Star, Trash2 } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import PhotoTile from "./PhotoTile"
import showConfirmToast from "./ConfirmToast"
import showInputToast from "./InputToast"
import showFormToast from "./FormToast"
import { useMemo } from "react"

export default function AlbumPhotoTile({ place, trip, album, photo, photoPosition, onPlaceHighlightCreated, onTripHighlightCreated, onPhotoReplaced, onMainPhotoUpdated }) {
    const { isAdmin } = useAuth()

    // TODO: This doesn't work well if there are multiple photos taken at the same time.
    const canPlaceHighlightBeAdded = useMemo(() => onPlaceHighlightCreated && place?.highlights && !place.highlights.some(highlight => highlight.timestamp === photo.timestamp), [place, photo])
    const canTripHighlightBeAdded = useMemo(() => onTripHighlightCreated && trip?.highlights && !trip.highlights.some(highlight => highlight.timestamp === photo.timestamp), [trip, photo])

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
            "Nahrazení fotky brzy začne",
            "Při nahrazování fotky došlo k chybě",
            async (path) => onPhotoReplaced(place.id, album.id, photo.id, path)
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
            <PhotoTile
                src={photo.url + "=w350-h233"}
                to={photo.permalink} />
            {isAdmin && (
                <div className="flex justify-center gap-2 mt-2">
                    {(canPlaceHighlightBeAdded || canTripHighlightBeAdded) && (
                            <button
                                onClick={handleHighlightCreated}
                                className="btn-large-gray">
                                <Plus size={16} />
                            </button>
                        )}
                    {onPhotoReplaced && (
                        <button
                            onClick={handlePhotoReplaced}
                            className="btn-large-gray">
                            <Edit2 size={16} />
                        </button>
                    )}
                    {onMainPhotoUpdated && photoPosition && album.mainPhotoId !== photo.id && (
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