import { ImageUp, SendToBack } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import PhotoTile from "./PhotoTile"
import { useState } from "react"

export default function HighlightCandidateTile({ name, description, categories, photo, onHighlightCandidateCreated }) {
    const [overlayType, setOverlayType] = useState(0)

    return (
        <div>
            {overlayType === 0 && (
                <PhotoTile
                    // TODO: Provide correct url in the caller.
                    src={photo.url + (photo.url.endsWith(".jpg") ? "" : "=w350-h233")}
                    to={photo.permalink} />
            )}
            {overlayType === 1 && (
                <PhotoTile
                    // TODO: Provide correct url in the caller.
                    src={photo.url + (photo.url.endsWith(".jpg") ? "" : "=w350-h233")}
                    to={photo.permalink}
                    categories={categories}
                    firstLineText={name} />
            )}
            {overlayType === 2 && (
                <PhotoTile
                    // TODO: Provide correct url in the caller.
                    src={photo.url + (photo.url.endsWith(".jpg") ? "" : "=w350-h233")}
                    to={photo.permalink}
                    categories={categories}
                    firstLineText={name}
                    secondLineText={description} />
            )}
            {onHighlightCandidateCreated && (
                <div className="flex justify-center gap-2 mt-2">
                    {onHighlightCandidateCreated && (
                        <button
                            onClick={() => onHighlightCandidateCreated(photo)}
                            className="btn-large-gray">
                            <ImageUp size={16} />
                        </button>
                    )}
                    {name && categories && (
                        <button
                            onClick={() => setOverlayType(prev => (prev + 1) % (description ? 3 : 2))}
                            className="btn-large-gray">
                            <SendToBack size={16} />
                        </button>
                    )}
                </div>
            )}
        </div>
    )
}