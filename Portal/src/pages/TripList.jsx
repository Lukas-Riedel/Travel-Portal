import { useYears } from "../hooks/useYears.js"
import { useMemo } from "react"
import PlaceMap from "../components/PlaceMap.jsx"
import StatisticsPanel from "../components/StatisticsPanel.jsx"
import { useStatistics } from "../hooks/useStatistics.js"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces.js"
import YearTileGrid from "../components/YearTileGrid.jsx"
import { useCategories } from "../hooks/useCategories.js"
import { useRegularTrips } from "../hooks/useRegularTrips.js"
import TripTable from "../components/TripTable.jsx"
import { useAuth } from "../contexts/AuthContext.jsx"

export default function TripList() {
    const { isAdmin } = useAuth()

    const years = useYears()
    const places = useTimeFilteredRegularPlaces({ include: "CATEGORIES" })
    const trips = useRegularTrips()
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
            {isAdmin && (
                <TripTable trips={trips?.filter(trip => trip?.isFuture() && !trip?.isDayTrips())} />
            )}
        </>
    )
}
