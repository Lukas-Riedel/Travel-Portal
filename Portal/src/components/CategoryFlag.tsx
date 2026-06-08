import type { Category } from "../types/CoreSwaggerTypes.ts"

interface CategoryFlagProps {
    category: Category | null
    className?: string
}

export default function CategoryFlag({ category, className }: CategoryFlagProps) {
    return category?.metadata?.unicode && (
        <img
            className={className}
            src={`/img/flags/${category.metadata?.unicode}.svg`}
            alt={category.name} />
    )
}