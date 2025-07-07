import { useParams } from "react-router-dom"
import PageHeader from "../components/PageHeader"
import HighlightCarouselAndPlaceMapToggle from "../components/HighlightCarouselAndPlaceMapToggle"
import StatisticsPanel from "../components/StatisticsPanel"
import { useMemo } from "react"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"
import { useYear } from "../hooks/useYear"
import { useRegularTrips } from "../hooks/useRegularTrips"
import TripTileGrid from "../components/TripTileGrid"
import TripTable from "../components/TripTable"
import { useAuth } from "../contexts/AuthContext"
import ExpenseSummary from "../components/ExpenseSummary"
import { getSortedTrips } from "../utils/helpers"

export default function YearPage() {
    const { isAdmin } = useAuth()
    const { year: yearParameter } = useParams()

    const { year, removeYearHighlight, updateYearMainHighlight } = useYear(yearParameter)
    const yearPlaces = useTimeFilteredRegularPlaces({ year: yearParameter, include: "CATEGORIES" })
    const yearTrips = useRegularTrips({ year: yearParameter, include: "EXPENSES" })

    const countryCategoriesMap = useMemo(() => new Map(yearPlaces?.map(place => place.getCategory("COUNTRY"))
        ?.filter(Boolean)?.map(category => [category.name, category])), [yearPlaces])

    const getPlaceCategory = place => countryCategoriesMap.get(place?.country)

    return (
        <>
            <PageHeader
                name={year?.id}
                categories={[...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))} />
            <HighlightCarouselAndPlaceMapToggle
                entity={year}
                places={yearPlaces}
                placeMainCategorySelector={getPlaceCategory}
                onHighlightRemoved={removeYearHighlight}
                onMainHighlightUpdated={updateYearMainHighlight} />
            <StatisticsPanel statistics={year?.statistics} />
            <TripTileGrid trips={yearTrips && getSortedTrips(yearTrips, isAdmin)} />
            {isAdmin && (
                <TripTable trips={yearTrips?.filter(trip => trip?.isFuture() && !trip?.isDayTrips())} />
            )}
            <ExpenseSummary expenses={yearTrips?.flatMap(trip => trip.expenses)} />
        </>
    )
}