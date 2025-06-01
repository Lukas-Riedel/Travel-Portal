import TileGrid from "./TileGrid.jsx"
import CategoryTile from "./CategoryTile.jsx"

export default function CategoryTileGrid({ categories }) {
    return categories?.length > 0 && (
        <TileGrid tiles={categories.map((category, index) => (
            <CategoryTile
                key={index}
                category={category} />
        ))} />
    )
}