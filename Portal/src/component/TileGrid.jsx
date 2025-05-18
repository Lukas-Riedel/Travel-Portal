export default function TileGrid({ tiles }) {
    if (tiles.length === 0) {
        return null
    }

    return (
        <div
            className="grid gap-4 justify-center my-4"
            style={{ gridTemplateColumns: "repeat(auto-fit, minmax(350px, 1fr))" }}>
            {tiles}
        </div>
    )
}