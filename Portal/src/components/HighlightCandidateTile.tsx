import { ImageUp, SendToBack } from "lucide-react"
import PhotoTile from "./PhotoTile"
import { useState } from "react"
import type { Category, Photo } from "../types/CoreSwaggerTypes"

interface HighlightCandidateTileProps {
    name: string | null
    description: string | null
    photo: Photo
    categories?: Category[]
    onHighlightCandidateCreated?: () => Promise<void>
}

export default function HighlightCandidateTile({ name, description, categories, photo, onHighlightCandidateCreated }: HighlightCandidateTileProps) {
    const [overlayType, setOverlayType] = useState(0)

    return (
        <div>
            {overlayType === 0 && (
                <PhotoTile
                    // TODO: Create a class with the method to obtain the thumbnail URL.
                    src={photo.url + (photo.url.endsWith(".jpg") ? "" : "=w350-h233")}
                    to={photo.permalink} />
            )}
            {overlayType === 1 && (
                <PhotoTile
                    // TODO: Create a class with the method to obtain the thumbnail URL.
                    src={photo.url + (photo.url.endsWith(".jpg") ? "" : "=w350-h233")}
                    to={photo.permalink}
                    categories={categories}
                    firstLineText={name} />
            )}
            {overlayType === 2 && (
                <PhotoTile
                    // TODO: Create a class with the method to obtain the thumbnail URL.
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
                            onClick={onHighlightCandidateCreated}
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