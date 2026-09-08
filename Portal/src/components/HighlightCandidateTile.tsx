import { ImageUp, Plus, SendToBack } from "lucide-react"
import { useState } from "react"

import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput"
import type { Category, Highlight,Photo } from "../types/CoreSwaggerTypes"
import PhotoTile from "./PhotoTile"

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
                    to={photo.permalink ?? undefined} />
            )}
            {overlayType === 1 && (
                <PhotoTile
                    // TODO: Create a class with the method to obtain the thumbnail URL.
                    src={photo.url + (photo.url.endsWith(".jpg") ? "" : "=w350-h233")}
                    to={photo.permalink ?? undefined}
                    categories={categories}
                    firstLineText={name ?? undefined} />
            )}
            {overlayType === 2 && (
                <PhotoTile
                    // TODO: Create a class with the method to obtain the thumbnail URL.
                    src={photo.url + (photo.url.endsWith(".jpg") ? "" : "=w350-h233")}
                    to={photo.permalink ?? undefined}
                    categories={categories}
                    firstLineText={name ?? undefined}
                    secondLineText={description} />
            )}
            {(onHighlightCreated || onHighlightCandidateCreated) && (
                <div className="flex justify-center gap-2 mt-2">
                    {onHighlightCreated && (
                        <button
                            onClick={handleHighlightCreated}
                            className="btn-pill">
                            <Plus size={16} />
                        </button>
                    )}
                    {onHighlightCandidateCreated && (
                        <button
                            onClick={onHighlightCandidateCreated}
                            className="btn-pill">
                            <ImageUp size={16} />
                        </button>
                    )}
                    {name && categories && (
                        <button
                            onClick={() => setOverlayType(prev => (prev + 1) % (description ? 3 : 2))}
                            className="btn-pill">
                            <SendToBack size={16} />
                        </button>
                    )}
                </div>
            )}
        </div>
    )
}