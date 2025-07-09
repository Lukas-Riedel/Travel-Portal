import CategoryCard from "./CategoryCard"

const loadingCategoriesCount = 4

export default function CategoryCardGrid({ categories, categoriesPlaces, onCurrentLocationChanged, onMaximumDistanceChanged, onPlaceRemoved }) {
    return (
        <div className="relative w-full my-4">
            <div className="grid grid-cols-[repeat(auto-fill,minmax(13rem,1fr))] gap-4 text-sm w-full">
                {categories?.map(category => (
                    <CategoryCard
                        key={category.id}
                        category={category}
                        places={categoriesPlaces && (categoriesPlaces[category.name] || [])}
                        onCurrentLocationChanged={onCurrentLocationChanged}
                        onMaximumDistanceChanged={onMaximumDistanceChanged}
                        onPlaceRemoved={onPlaceRemoved} />
                )) ?? (
                        Array.from({ length: loadingCategoriesCount }).map((_, index) => (
                            <CategoryCard key={index} />
                        ))
                    )}
            </div>
        </div>
    )
}