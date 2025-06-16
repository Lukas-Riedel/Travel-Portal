import { useYears } from "../hooks/useYears"
import { useMemo } from "react"
import PlaceMap from "../components/PlaceMap.jsx"
import StatisticsPanel from "../components/StatisticsPanel.jsx"
import { useStatistics } from "../hooks/useStatistics"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces.js"
import YearTileGrid from "../components/YearTileGrid.jsx"
import { useCategories } from "../hooks/useCategories.js"

export default function YearList() {
    const years = useYears()
    const places = useTimeFilteredRegularPlaces({ include: "CATEGORIES" })
    const countryCategories = useCategories({ categories: "COUNTRY" })
    const statistics = useStatistics()

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

    return (
        <>
            <div className="h-[400px] md:h-[700px]">
                <PlaceMap
                    places={places}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)} />
            </div>
            <StatisticsPanel statistics={statistics} />
            <YearTileGrid years={years?.filter(year => year.mainHighlight)} />
        </>
    )
}
