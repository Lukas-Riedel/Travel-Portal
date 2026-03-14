import CardGrid from "./CardGrid.tsx"
import CategoryCard from "./CategoryCard"

export default function CategoryCardGrid({ categories, categoriesPlaces, onCurrentLocationChanged, onMaximumDistanceChanged, onPlaceRemoved }) {
    return (
        <CardGrid rowSize={5}>
            {categories?.map(category => (
                <CategoryCard
                    key={category.id}
                    category={category}
                    places={categoriesPlaces && (categoriesPlaces[category.name] || [])}
                    onCurrentLocationChanged={onCurrentLocationChanged}
                    onMaximumDistanceChanged={onMaximumDistanceChanged}
                    onPlaceRemoved={onPlaceRemoved} />
            ))}
        </CardGrid>
    )
}