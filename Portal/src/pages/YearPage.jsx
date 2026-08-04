import { useParams } from "react-router-dom"
import PageHeader from "../components/PageHeader"
import HighlightCarouselAndPlaceMapAndFlightMapToggleToggle from "../components/HighlightCarouselAndPlaceMapAndFlightMapToggleToggle"
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
import { UserRole } from "../types/CoreSwaggerTypes.ts"
import { useCategories } from "../hooks/useCategories.ts"

export default function YearPage() {
    const { hasRole } = useAuth()
    const { configuration } = useConfiguration()
    const { year: yearParameter } = useParams()
    const { publishPhotoReplacingTriggeredEvent, publishPhotosUploadingTriggeredEvent } = useEvents()

    const { year, removeYearHighlight, updateYearMainHighlight, updateYearHighlightQualityAttributes, refreshYearHighlights } = useYear(yearParameter)
    const { places } = useTimeFilteredRegularPlaces({ year: yearParameter, include: ["dates", "categories", "notes"] })
    const { trips: yearTrips } = useRegularTrips({ year: yearParameter, include: ["expenses", "flights"] })
    const countryCategories = useCategories({ categories: ["country"] })

    const flights = useMemo(() => (yearTrips ?? []).flatMap(trip => trip.flights).filter(Boolean).filter(flight => flight.registration), [yearTrips])
    const timezone = useMemo(() => configuration?.homeLocation?.timezone, [configuration])
    const placesWithoutTrip = useMemo(() => places?.map(place => place.withFilteredDates(date => !date.trip))?.filter(place => place.dates?.length > 0), [places])
    const days = useMemo(() => Array.from(new Set(placesWithoutTrip?.flatMap(p => p.dates?.map(d => startOfDay(fromUnixTime(d.start, { timeZone: timezone })).getTime()) ?? []))).sort((a, b) => a - b).map(ts => new Date(ts)), [placesWithoutTrip, timezone])

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])

    const visitedCountriesMap = useMemo(() => new Map(places?.map(place => place.getCategory("country"))
        ?.filter(Boolean)?.map(category => [category.name, category])), [places])

    const getPlaceCategory = place => countryCategoriesMap.get(place?.country)
    const getAirportCategory = airport => countryCategoriesMap.get(airport.country)
    const getDayOfYear = date => Math.round((Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()) - Date.UTC(date.getFullYear(), 0, 1)) / 86400000)

    const handlePhotoCorrected = async (placeId, albumId, fileName, data, replacedPhotoId) => createPlaceAlbumPhoto(placeId, albumId, fileName, data, replacedPhotoId).then(({ batchId }) => refreshPlaceAlbum(placeId, albumId, { batchId }))

    // TODO: Introduce Calendar instead of CardGrid, use TripCalendar as base. Also make sure that loading tail spins are displayed correctly.
    return hasRole(UserRole.YearRead) && (
        <>
            <PageHeader
                name={year?.id}
                categories={[...visitedCountriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))}
                internalAttributes={hasRole(UserRole.YearEdit) && { "Počet highlightů": year?.highlights?.length }}
                onHighlightsRefreshed={hasRole(UserRole.YearHighlightEdit) && yearTrips?.some(trip => trip.mainHighlight) && (highlightsCount => refreshYearHighlights(highlightsCount))}
            />
            <HighlightCarouselAndPlaceMapAndFlightMapToggleToggle
                entity={year}
                places={places}
                flights={flights}
                placeMainCategorySelector={getPlaceCategory}
                airportMainCategorySelector={getAirportCategory}
                onPhotoReplaced={hasRole(UserRole.PlaceAlbumEdit) && publishPhotoReplacingTriggeredEvent}
                onPhotoCorrected={hasRole(UserRole.PlaceAlbumEdit) && handlePhotoCorrected}
                onHighlightRemoved={hasRole(UserRole.YearHighlightEdit) && removeYearHighlight}
                onMainHighlightUpdated={hasRole(UserRole.YearEdit) && updateYearMainHighlight}
                onHighlightQualityAttributesUpdated={hasRole(UserRole.HighlightEdit) && updateYearHighlightQualityAttributes} />
            <StatisticsPanel statistics={year && (year.statistics ?? [])} />
            {hasRole(UserRole.PortalFutureRead) && (
                <TripTable trips={yearTrips?.filter(trip => trip.isFuture())} />
            )}
            <TripTileGrid trips={yearTrips?.filter(trip => trip.isPast())?.slice()?.reverse()} />
            <CardGrid rowSize={4}>
                {days?.map((day, index) => (
                    <DayCard
                        key={index}
                        day={day}
                        // TODO: Extract the function somewhere.
                        events={placesWithoutTrip && new Trip({}).getCalendarEvents(day, placesWithoutTrip, timezone)}
                        fitness={year?.fitness && year.fitness[getDayOfYear(day)]}
                        timezone={timezone}
                        displayWarnings={hasRole(UserRole.PortalWarningRead)}
                        onPhotosAdded={hasRole(UserRole.PlaceAlbumEdit) && publishPhotosUploadingTriggeredEvent} />
                ))}
            </CardGrid>
            <ExpenseSummary expenses={yearTrips?.filter(trip => trip.isPast() || trip.isCurrent())?.flatMap(trip => trip.expenses ?? [])} />
        </>
    )
}