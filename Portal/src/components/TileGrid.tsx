import LoadingTile from "./LoadingTile.tsx";

const LOADING_TILES_COUNT = 3

interface TileGridProps {
    children?: React.ReactNode
}

export default function TileGrid({ children }: TileGridProps) {
    return (
        <div
            className="grid gap-4 justify-center my-4"
            style={{ gridTemplateColumns: "repeat(auto-fit, minmax(350px, 1fr))" }}>
            {children || Array.from({ length: LOADING_TILES_COUNT }, (_, index) => (
                <LoadingTile key={index} />
            ))}
        </div>
    )
}