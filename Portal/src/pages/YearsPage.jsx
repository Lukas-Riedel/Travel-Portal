import { useMemo } from "react"
import PlaceMap from "../components/PlaceMap.jsx"
import StatisticsPanel from "../components/StatisticsPanel.jsx"
import { useStatistics } from "../hooks/useStatistics.js"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces.js"
import { useCategories } from "../hooks/useCategories.js"
import { useRegularTrips } from "../hooks/useRegularTrips.js"
import TripTable from "../components/TripTable.jsx"
import { useAuth } from "../contexts/AuthContext.jsx"
import { useYears } from "../hooks/useYears.js"
import YearTripTileGrid from "../components/YearTripTileGrid.jsx"

export default function YearsPage() {
    const { isAdmin } = useAuth()

    const years = useYears()
    const places = useTimeFilteredRegularPlaces({ include: "categories" })
    const trips = useRegularTrips()
    const countryCategories = useCategories({ categories: "country" })
    const statistics = useStatistics()

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
            <StatisticsPanel statistics={statistics} />
            {isAdmin && (
                <TripTable trips={trips?.filter(trip => trip?.isFuture() && !trip?.isDayTrips())} />
            )}
            {(years?.filter(year => year.mainHighlight)?.map(year => year.id) ?? [new Date().getFullYear()]).map(year => (
                <YearTripTileGrid
                    key={year}
                    year={year}
                    trips={trips} />
            ))}
        </>
    )
}
