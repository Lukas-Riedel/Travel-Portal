import { useMemo } from "react";
import PlaceMap from "../components/PlaceMap";
import StatisticsCardGrid from "../components/StatisticsCardGrid";
import { useStatistics } from "../hooks/useStatistics";
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces";
import { useCategories } from "../hooks/useCategories";
import { useYears } from "../hooks/useYears.ts";

export default function StatisticsPage() {
    const statistics = useStatistics()
    const years = useYears({ include: ["statistics"] })
    const { places } = useTimeFilteredRegularPlaces({ sort: "-score" })
    const countryCategories = useCategories({ categories: ["country"] })

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
            <StatisticsCardGrid
                statistics={statistics}
                years={years} />
        </>
    )
}