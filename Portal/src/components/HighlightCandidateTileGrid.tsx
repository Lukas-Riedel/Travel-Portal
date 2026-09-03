import { useState, useEffect } from "react"
import HighlightCandidateTile from "./HighlightCandidateTile"
import TileGrid from "./TileGrid.js"
import { Expand } from "lucide-react"
import type { Category, Photo, Highlight } from "../types/CoreSwaggerTypes"
import type { HighlightCandidatesGroup } from "../types/HighlightCandidatesGroup"

interface HighlightCandidateTileGridProps {
    name: string | null
    description: string | null
    categories?: Category[]
    highlightCandidatesGroups: HighlightCandidatesGroup[]
    onHighlightCreated?: (photoId: string) => Promise<Highlight>
    onHighlightCandidateCreated?: (photo: Photo) => Promise<void>
}

export default function HighlightCandidateTileGrid({ name, description, categories, highlightCandidatesGroups, onHighlightCreated, onHighlightCandidateCreated }: HighlightCandidateTileGridProps) {
    return highlightCandidatesGroups?.map(highlightCandidatesGroup => (
        <HighlightCandidateTileGridGroup
            key={highlightCandidatesGroup.title}
            name={name}
            description={description}
            categories={categories}
            highlightCandidatesGroup={highlightCandidatesGroup}
            shouldLoadOnRender={highlightCandidatesGroups.length === 1}
            onHighlightCreated={onHighlightCreated}
            onHighlightCandidateCreated={onHighlightCandidateCreated} />
    ))
}

interface HighlightCandidateTileGridGroupProps {
    name: string | null
    description: string | null
    categories?: Category[]
    highlightCandidatesGroup: HighlightCandidatesGroup
    shouldLoadOnRender: boolean
    onHighlightCreated?: (photoId: string) => Promise<Highlight>
    onHighlightCandidateCreated?: (photo: Photo) => Promise<void>
}

function HighlightCandidateTileGridGroup({ name, description, categories, highlightCandidatesGroup, shouldLoadOnRender, onHighlightCreated, onHighlightCandidateCreated }: HighlightCandidateTileGridGroupProps) {
    const [photos, setPhotos] = useState<Photo[] | null>(null)
    const [isLoading, setIsLoading] = useState(false)

    const handlePhotosLoaded = async () => {
        setIsLoading(true)
        setPhotos(await highlightCandidatesGroup.getPhotos())
        setIsLoading(false)
    }

    useEffect(() => {
        if (shouldLoadOnRender) {
            handlePhotosLoaded()
        }
    }, [shouldLoadOnRender, handlePhotosLoaded])

    return (
        <div key={highlightCandidatesGroup.title}>
            <div className="flex flex-col items-center my-6 space-y-2 text-center">
                <span className="text-2xl font-semibold">
                    {highlightCandidatesGroup.title}
                </span>
                {photos === null && !isLoading && (
                    <button
                        onClick={handlePhotosLoaded}
                        className="btn-chip-gray">
                        <Expand size={16} />
                    </button>
                )}
            </div>
            {(photos || isLoading) && (
                <TileGrid>
                    {photos?.map(photo => (
                        <HighlightCandidateTile
                            key={photo.id}
                            name={name}
                            description={description}
                            categories={categories}
                            photo={photo}
                            onHighlightCreated={onHighlightCreated && (() => onHighlightCreated(photo.id))}
                            onHighlightCandidateCreated={onHighlightCandidateCreated && (() => onHighlightCandidateCreated(photo))} />
                    ))}
                </TileGrid>
            )}
        </div>
    )
}