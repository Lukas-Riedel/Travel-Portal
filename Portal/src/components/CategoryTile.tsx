import type { Category } from "../types/CoreSwaggerTypes.ts"
import PhotoTile from "./PhotoTile.tsx"

interface CategoryTileProps {
    category: Category | null
}

export default function CategoryTile({ category }: CategoryTileProps) {
    return (!category || category.mainHighlight) && (
        <PhotoTile
            src={category?.mainHighlight?.url?.thumbnail ?? category?.mainHighlight?.url?.full}
            firstLineText={category?.name}
            categories={category ? [category] : []}
            to={category} />
    )
}
