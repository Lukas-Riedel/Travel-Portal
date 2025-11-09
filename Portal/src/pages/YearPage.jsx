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
import { useEvents } from "../hooks/useEvents"
import { createPlaceAlbumPhoto, refreshPlaceAlbum } from "../clients/coreClient"
import CardGrid from "../components/CardGrid"
import DayCard from "../components/DayCard"
import { fromUnixTime, startOfDay } from "date-fns"
import { useConfiguration } from "../contexts/ConfigContext"
import { Trip } from "../classes/Trip.ts"

export default function YearPage() {
    const { isAdmin } = useAuth()
    const { configuration } = useConfiguration()
    const { year: yearParameter } = useParams()
    const { publishPhotoReplacingTriggeredEvent, publishPhotosUploadingTriggeredEvent } = useEvents()

    const { year, removeYearHighlight, updateYearMainHighlight, updateYearHighlightQualityAttributes } = useYear(yearParameter)
    const { places } = useTimeFilteredRegularPlaces({ year: yearParameter, include: "dates,categories" })
    const yearTrips = useRegularTrips({ year: yearParameter, include: "expenses" })

    const timezone = useMemo(() => configuration?.homeLocation?.timezone, [configuration])
    const placesWithoutTrip = useMemo(() => places?.map(place => place.withFilteredDates(date => !date.trip))?.filter(place => place.dates?.length > 0), [places])
    const days = useMemo(() => Array.from(new Set(placesWithoutTrip?.flatMap(p => p.dates?.map(d => startOfDay(fromUnixTime(d.start, { timeZone: timezone })).getTime()) ?? []))).sort((a, b) => a - b).map(ts => new Date(ts)), [placesWithoutTrip, timezone])

    const countryCategoriesMap = useMemo(() => new Map(places?.map(place => place.getCategory("country"))
        ?.filter(Boolean)?.map(category => [category.name, category])), [places])

    const getPlaceCategory = place => countryCategoriesMap.get(place?.country)
    const getDayOfYear = date => Math.round((Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()) - Date.UTC(date.getFullYear(), 0, 1)) / 86400000) + 1

    const handlePhotoCorrected = async (placeId, albumId, fileName, data, replacedPhotoId) => createPlaceAlbumPhoto(placeId, albumId, fileName, data, replacedPhotoId).then(() => refreshPlaceAlbum(placeId, albumId))

    // TODO: Introduce Calendar instead of CardGrid, use TripCalendar as base. Also make sure that loading tail spins are displayed correctly.
    return (
        <>
            <PageHeader
                name={year?.id}
                categories={[...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))}
                showHighlightsButton={yearTrips?.some(trip => trip.mainHighlight)} />
            <HighlightCarouselAndPlaceMapToggle
                entity={year}
                places={places}
                placeMainCategorySelector={getPlaceCategory}
                onPhotoReplaced={publishPhotoReplacingTriggeredEvent}
                onPhotoCorrected={handlePhotoCorrected}
                onHighlightRemoved={removeYearHighlight}
                onMainHighlightUpdated={updateYearMainHighlight}
                onHighlightQualityAttributesUpdated={updateYearHighlightQualityAttributes} />
            <StatisticsPanel statistics={year && (year.statistics ?? [])} />
            <TripTileGrid trips={yearTrips?.slice()?.reverse()} />
            <CardGrid cardsPerRowCount={4}>
                {days?.map((day, index) => (
                    <DayCard
                        key={index}
                        day={day}
                        // TODO: Extract the function somewhere.
                        events={placesWithoutTrip && new Trip({}).getCalendarEvents(day, placesWithoutTrip, timezone)}
                        fitness={year?.fitness && year.fitness[getDayOfYear(day)]}
                        timezone={timezone}
                        onPhotosAdded={publishPhotosUploadingTriggeredEvent} />
                ))}
            </CardGrid>
            {isAdmin && (
                <TripTable trips={yearTrips?.filter(trip => trip?.isFuture())} />
            )}
            <ExpenseSummary expenses={yearTrips?.flatMap(trip => trip.expenses ?? [])} />
        </>
    )
}