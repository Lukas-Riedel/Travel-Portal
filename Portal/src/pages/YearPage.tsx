import { startOfDay } from "date-fns"
import { useMemo } from "react"
import { useTranslation } from "react-i18next"
import { useParams } from "react-router-dom"

import type { Place } from "../classes/Place.ts"
import { createPlaceAlbumPhoto, listPlaceAlbumPhotos, refreshPlaceAlbum } from "../clients/coreClient"
import CardGrid from "../components/CardGrid"
import DayCard from "../components/DayCard"
import ExpenseSummary from "../components/ExpenseSummary"
import HighlightCarouselAndPlaceMapAndFlightMapToggleToggle from "../components/HighlightCarouselAndPlaceMapAndFlightMapToggleToggle.tsx"
import PageHeader from "../components/PageHeader"
import StatisticsPanel from "../components/StatisticsPanel"
import TripTable from "../components/TripTable"
import TripTileGrid from "../components/TripTileGrid"
import { useAuth } from "../contexts/AuthContext"
import { useConfiguration } from "../contexts/ConfigContext"
import { useCountryCategoriesMap } from "../hooks/useCountryCategoriesMap.ts"
import { useEvents } from "../hooks/useEvents"
import { useRegularTrips } from "../hooks/useRegularTrips"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"
import { useYear } from "../hooks/useYear"
import { type Airport, CategoryCategory, PlaceIncludedEntity, TripIncludedEntity, UserRole } from "../types/CoreSwaggerTypes.ts"
import { getZonedDate } from "../utils/timeUtils.ts"
import { getCalendarEvents } from "../utils/tripUtils.ts"

export default function YearPage() {
    const { year: yearParameter } = useParams()
    const { hasRole } = useAuth()
    const { t } = useTranslation()
    const { publishPhotoReplacingTriggeredEvent, publishPhotosUploadingTriggeredEvent } = useEvents()
    const { configuration } = useConfiguration()

    const { year, removeYearHighlight, updateYearMainHighlight, updateYearHighlightQualityAttributes, refreshYearHighlights } = useYear(Number(yearParameter))
    const { places } = useTimeFilteredRegularPlaces({ year: Number(yearParameter), include: [PlaceIncludedEntity.Dates, PlaceIncludedEntity.Categories, PlaceIncludedEntity.Notes] })
    const { trips: yearTrips } = useRegularTrips({ year: Number(yearParameter), include: [TripIncludedEntity.Expenses, TripIncludedEntity.Flights] })
    const countryCategoriesMap = useCountryCategoriesMap()

    const flights = useMemo(() => (yearTrips ?? []).flatMap(trip => trip.flights).filter(Boolean).filter(flight => flight.registration), [yearTrips])
    const timezone = useMemo(() => configuration?.homeLocation?.timezone, [configuration])
    const placesWithoutTrip = useMemo(() => places?.map(place => place.withFilteredDates(date => !date.trip))?.filter(place => place.dates?.length > 0), [places])
    const days = useMemo(() => Array.from(new Set(placesWithoutTrip?.flatMap(p => p.dates?.map(d => startOfDay(getZonedDate(d.start, timezone)).getTime()) ?? [])))
        .sort((a, b) => a - b).map(timestamp => new Date(timestamp)), [placesWithoutTrip, timezone])

    const visitedCountriesMap = useMemo(() => new Map(places?.map(place => place.getCategory(CategoryCategory.Country))
        ?.filter(Boolean)?.map(category => [category.name, category])), [places])

    const attributes = {
        [t("year.attribute.highlightsCount")]: year?.highlights?.length
    }

    const getPlaceCategory = (place: Place) => countryCategoriesMap.get(place?.country)
    const getAirportCategory = (airport: Airport) => countryCategoriesMap.get(airport.country)
    const getDayOfYear = date => Math.round((Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()) - Date.UTC(date.getFullYear(), 0, 1)) / 86400000)

    const handlePhotoCorrected = async (placeId: string, albumId: string, fileName: string, base64Data: string, photoId: string) => createPlaceAlbumPhoto(placeId, albumId, fileName, base64Data, photoId)
        .then(({ batchId }) => refreshPlaceAlbum(placeId, albumId, { batchId }))
        .then(_ => listPlaceAlbumPhotos(placeId, albumId))
        .then(photos => photos.find(photo => photo.id === photoId))

    // TODO: Introduce Calendar instead of CardGrid, use TripCalendar as base. Also make sure that loading tail spins are displayed correctly.
    return hasRole(UserRole.YearRead) && (
        <>
            <PageHeader
                name={yearParameter ?? null}
                categories={[...visitedCountriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))}
                internalAttributes={hasRole(UserRole.YearEdit) && attributes}
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
                        events={placesWithoutTrip && getCalendarEvents(day, [], [], placesWithoutTrip, timezone)}
                        fitness={year?.fitness && year.fitness[getDayOfYear(day)]}
                        timezone={timezone}
                        displayWarnings={hasRole(UserRole.PortalWarningRead)}
                        onPhotosAdded={hasRole(UserRole.PlaceAlbumEdit) && publishPhotosUploadingTriggeredEvent} />
                ))}
            </CardGrid>
            {hasRole(UserRole.TripExpenseRead) && (
                <ExpenseSummary expenses={yearTrips?.filter(trip => trip.isPast() || trip.isCurrent())?.flatMap(trip => trip.expenses ?? [])} />
            )}
        </>
    )
}
