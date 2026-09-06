import { ImageUp, Plus, SendToBack } from "lucide-react"
import PhotoTile from "./PhotoTile"
import { useState } from "react"
import type { Category, Photo, Highlight } from "../types/CoreSwaggerTypes"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput"

interface HighlightCandidateTileProps {
    name: string | null
    description?: string
    photo: Photo
    categories?: Category[]
    onHighlightCreated?: () => Promise<Highlight>
    onHighlightCandidateCreated?: () => Promise<void>
}

export default function HighlightCandidateTile({ name, description, categories, photo, onHighlightCreated, onHighlightCandidateCreated }: HighlightCandidateTileProps) {
    const { showCreateHighlightToast } = usePredefinedUserInput()
    const [overlayType, setOverlayType] = useState(0)


    const handleHighlightCreated = () => {
        if (onHighlightCreated) {
            showCreateHighlightToast(onHighlightCreated)
        }
    }

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
            {(onHighlightCreated || onHighlightCandidateCreated) && (
                <div className="flex justify-center gap-2 mt-2">
                    {onHighlightCreated && (
                        <button
                            onClick={handleHighlightCreated}
                            className="btn-large-gray">
                            <Plus size={16} />
                        </button>
                    )}
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