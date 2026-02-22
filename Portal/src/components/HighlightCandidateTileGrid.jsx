import { useState } from "react"
import HighlightCandidateTile from "./HighlightCandidateTile.jsx"
import TileGrid from "./TileGrid.jsx"
import { Expand } from "lucide-react"

export default function HighlightCandidateTileGrid({ name, description, categories, highlightCandidates, onHighlightCandidateCreated }) {
    return highlightCandidates?.map(group => (
        <HighlightCandidateTileGridGroup
            key={group.title}
            name={name}
            description={description}
            categories={categories}
            group={group}
            onHighlightCandidateCreated={onHighlightCandidateCreated} />
    ))
}

function HighlightCandidateTileGridGroup({ name, description, categories, group, onHighlightCandidateCreated }) {
    const [photos, setPhotos] = useState(null)
    const [isLoading, setIsLoading] = useState(false)

    const handlePhotosLoaded = async () => {
        setIsLoading(true)
        setPhotos(await group.getPhotos())
        setIsLoading(false)
    }

    return (
        <div key={group.title}>
            <div className="flex flex-col items-center my-6 space-y-2 text-center">
                <span className="text-2xl font-semibold">
                    {group.title}
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
                            onHighlightCandidateCreated={onHighlightCandidateCreated} />
                    ))}
                </TileGrid>
            )}
        </div>
    )
}