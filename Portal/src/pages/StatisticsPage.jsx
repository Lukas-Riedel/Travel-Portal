import { useMemo } from "react";
import PlaceMap from "../components/PlaceMap";
import StatisticsCardGrid from "../components/StatisticsCardGrid";
import { useStatistics } from "../hooks/useStatistics";
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces";
import { useCategories } from "../hooks/useCategories";

export default function StatisticsPage() {
    const statistics = useStatistics()
    const { places } = useTimeFilteredRegularPlaces({ include: "categories" })
    const { categories: countryCategories } = useCategories({ categories: "country" })

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

    return (
        <>
            <div className="h-[400px] md:h-[700px] my-4">
                <PlaceMap
                    places={places}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)} />
            </div>
            <StatisticsCardGrid statistics={statistics} />
        </>
    )
}