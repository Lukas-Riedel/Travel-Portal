import type { Category } from "../types/CoreSwaggerTypes.ts"
import CategoryTile from "./CategoryTile.tsx"
import TileGrid from "./TileGrid.tsx"

interface CategoryTileGridProps {
    categories: Category[] | null
}

export default function CategoryTileGrid({ categories }: CategoryTileGridProps) {
    return (!categories || categories.length > 0) && (
        <TileGrid>
            {categories?.map(category => (
                <CategoryTile
                    key={category.id}
                    category={category} />
            ))}
        </TileGrid>
    )
}