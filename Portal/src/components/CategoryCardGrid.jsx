import CardGrid from "./CardGrid"
import CategoryCard from "./CategoryCard"

export default function CategoryCardGrid({ categories, categoriesPlaces, onCurrentLocationChanged, onMaximumDistanceChanged, onPlaceRemoved }) {
    return (
        <CardGrid cardsPerRowCount={5}>
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