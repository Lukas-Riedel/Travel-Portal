import type { Category } from "../types/CoreSwaggerTypes.ts"
import { getEntityPrettyName } from "../utils/formattingUtils.ts"
import AppLink from "./AppLink.tsx"
import Bar from "./Bar.tsx"
import BarItem from "./BarItem.tsx"

interface CategoryBarProps {
    categories: Category[] | null
}

export default function CategoryBar({ categories }: CategoryBarProps) {
    return (!categories || categories.length > 0) && (
        <Bar>
            {categories && categories.map(category => (
                <BarItem
                    key={category.id}
                    to={category}>
                    {getEntityPrettyName(category.name)}
                </BarItem>
            ))}
        </Bar>
    )
}
