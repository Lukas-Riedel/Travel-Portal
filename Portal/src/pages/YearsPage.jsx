import { useMemo } from "react"
import PlaceMap from "../components/PlaceMap.jsx"
import StatisticsPanel from "../components/StatisticsPanel.jsx"
import { useStatistics } from "../hooks/useStatistics.js"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces.js"
import { useCategories } from "../hooks/useCategories.js"
import { useRegularTrips } from "../hooks/useRegularTrips.js"
import TripTable from "../components/TripTable.jsx"
import { useAuth } from "../contexts/AuthContext.jsx"
import TripSummary from "../components/TripSummary.jsx"
import { useYears } from "../hooks/useYears.js"
import YearTripTileGrid from "../components/YearTripTileGrid.jsx"

export default function YearsPage() {
    const { isAdmin } = useAuth()

    const years = useYears()
    const places = useTimeFilteredRegularPlaces({ include: "CATEGORIES" })
    const trips = useRegularTrips()
    const countryCategories = useCategories({ categories: "COUNTRY" })
    const statistics = useStatistics()

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

    const upcomingOrCurrentTrip = useMemo(() => trips?.filter(trip => !trip?.isDayTrips())?.find(trip => trip?.isCurrent() || trip?.isFuture()), [trips])

    return (
        <>
            <div className="h-[400px] md:h-[700px] my-4">
                <PlaceMap
                    places={places}
                    placeMainCategorySelector={place => countryCategoriesMap.get(place.country)} />
            </div>
            <StatisticsPanel statistics={statistics} />
            {(isAdmin || upcomingOrCurrentTrip?.isCurrent()) && (
                <TripSummary tripId={upcomingOrCurrentTrip?.id} />
            )}
            {(years?.filter(year => year.mainHighlight)?.map(year => year.id) ?? [new Date().getFullYear()]).map((year, index) => (
                <YearTripTileGrid
                    key={index}
                    year={year}
                    trips={trips} />
            ))}
            {isAdmin && (
                <TripTable trips={trips?.filter(trip => trip?.isFuture() && !trip?.isDayTrips())} />
            )}
        </>
    )
}
