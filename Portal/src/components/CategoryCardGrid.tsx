import type { DistanceAwarePlace } from "../classes/DistanceAwarePlace.ts"
import type { Place } from "../classes/Place.ts"
import type { Category } from "../types/CoreSwaggerTypes.ts"
import CardGrid from "./CardGrid.tsx"
import CategoryCard from "./CategoryCard.tsx"

interface CategoryCardGridProps {
    categories: Category[] | null
    categoriesPlaces: Record<string, DistanceAwarePlace[] | Place[]> | null
    rowSize: number
    columnSize?: number
    onCurrentLocationChanged?: (place: Place) => void
    onMaximumDistanceChanged?: (distance: number) => void
    onPlaceRemoved?: (placeId: string) => Promise<void>
}

const haveDistance = (places: DistanceAwarePlace[] | Place[]): places is DistanceAwarePlace[] => places.length > 0 && (places[0] as DistanceAwarePlace).distance !== undefined

export default function CategoryCardGrid({ categories, categoriesPlaces, rowSize, columnSize, onCurrentLocationChanged, onMaximumDistanceChanged, onPlaceRemoved }: CategoryCardGridProps) {
    const getSortedPlaces = (categoryName: string) => {
        const places = categoriesPlaces?.[categoryName] || []
        return haveDistance(places)
            ? [...places].sort((a, b) => a.distance - b.distance)
            : places
    }

    return (
        <CardGrid
            rowSize={rowSize}
            columnSize={columnSize}>
            {categories?.map(category => (
                <CategoryCard
                    key={category.id}
                    category={category}
                    places={categoriesPlaces && getSortedPlaces(category.name)}
                    onCurrentLocationChanged={onCurrentLocationChanged}
                    onMaximumDistanceChanged={onMaximumDistanceChanged}
                    onPlaceRemoved={onPlaceRemoved} />
            ))}
        </CardGrid>
    )
}