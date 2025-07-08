import CandidateCategoryCard from "./CandidateCategoryCard"

const loadingCategoriesCount = 4

export default function CandidateCategoryCardGrid({ categories, categoriesPlaces, onCurrentLocationChanged, onMaximumDistanceChanged, onCandidatePlaceRemoved }) {
    return (
        <div className="relative w-full my-4">
            <div className="grid grid-cols-[repeat(auto-fill,minmax(13rem,1fr))] gap-4 text-sm w-full">
                {categories?.map((category, idx) => (
                    <CandidateCategoryCard
                        key={idx}
                        category={category}
                        places={categoriesPlaces && (categoriesPlaces[category.name] || [])}
                        onCurrentLocationChanged={onCurrentLocationChanged}
                        onMaximumDistanceChanged={onMaximumDistanceChanged}
                        onCandidatePlaceRemoved={onCandidatePlaceRemoved} />
                )) ?? (
                        Array.from({ length: loadingCategoriesCount }).map((_, idx) => (
                            <CandidateCategoryCard key={idx} />
                        ))
                    )}
            </div>
        </div>
    )
}