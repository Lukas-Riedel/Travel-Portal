import LoadingTile from "./LoadingTile";

const loadingTilesCount = 3

export default function TileGrid({ children }) {
    return (
        <div
            className="grid gap-4 justify-center my-4"
            style={{ gridTemplateColumns: "repeat(auto-fit, minmax(350px, 1fr))" }}>
            {children || Array.from({ length: loadingTilesCount }, (_, index) => (
                <LoadingTile key={index} />
            ))}
        </div>
    )
}