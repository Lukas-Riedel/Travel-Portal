import HighlightCandidateTile from "./HighlightCandidateTile.jsx"
import TileGrid from "./TileGrid.jsx"

export default function HighlightCandidateTileGrid({ name, description, categories, highlightCandidates, onHighlightCandidateCreated }) {
    return highlightCandidates?.map(group => (
        <div key={group.title}>
            <div className="text-center my-4">
                <span className="text-2xl font-semibold">
                    {group.title}
                </span>
            </div>
            <TileGrid>
                {group.highlightCandidates.map(photo => (
                    <HighlightCandidateTile
                        key={photo.id}
                        name={name}
                        description={description}
                        categories={categories}
                        photo={photo}
                        onHighlightCandidateCreated={onHighlightCandidateCreated} />
                ))}
            </TileGrid>
        </div>
    ))
}