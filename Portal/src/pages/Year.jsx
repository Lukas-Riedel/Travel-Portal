import { useParams } from "react-router-dom"
import PageHeader from "../components/PageHeader"
import HighlightCarouselAndPlaceMapToggle from "../components/HighlightCarouselAndPlaceMapToggle"
import StatisticsPanel from "../components/StatisticsPanel"
import { useMemo } from "react"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"
import { useYear } from "../hooks/useYear"
import { useRegularTrips } from "../hooks/useRegularTrips"
import TripTileGrid from "../components/TripTileGrid"

export default function Year() {
    const { year: yearParameter } = useParams()

    const { year, removeYearHighlight, updateYearMainHighlight } = useYear(yearParameter)
    const yearPlaces = useTimeFilteredRegularPlaces({ year: yearParameter, include: "CATEGORIES" })
    const yearTrips = useRegularTrips({ year: yearParameter })

    const dayTripsTrip = useMemo(() => yearTrips?.find(trip => trip.isDayTrips()), [yearTrips])
    const countryCategoriesMap = useMemo(() => new Map(yearPlaces?.map(place => place.getCategory("COUNTRY"))
        ?.filter(category => category)?.map(category => [category.name, category])), [yearPlaces])

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
            <TripTileGrid trips={yearTrips && [...(yearTrips.filter(trip => trip.id !== dayTripsTrip?.id).reverse()), dayTripsTrip]} />
        </>
    )
}