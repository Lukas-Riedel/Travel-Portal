import { useMemo } from "react"
import { useTranslation } from "react-i18next"
import { useParams } from "react-router-dom"

import type { Place } from "../classes/Place.ts"
import { createPlaceAlbumPhoto, listPlaceAlbumPhotos, refreshPlaceAlbum } from "../clients/coreClient"
import ExpenseSummary from "../components/ExpenseSummary"
import HighlightCarouselAndPlaceMapAndFlightMapToggleToggle from "../components/HighlightCarouselAndPlaceMapAndFlightMapToggleToggle.tsx"
import NoteCardGrid from "../components/NoteCardGrid.jsx"
import PageHeader from "../components/PageHeader"
import PlaceTileGrid from "../components/PlaceTileGrid"
import StatisticsPanel from "../components/StatisticsPanel"
import TripCalendar from "../components/TripCalendar.tsx"
import TripNavigation from "../components/TripNavigation"
import { useAuth } from "../contexts/AuthContext"
import { useCandidatePlaces } from "../hooks/useCandidatePlaces"
import { useCandidateTrips } from "../hooks/useCandidateTrips"
import { useCountryCategoriesMap } from "../hooks/useCountryCategoriesMap.ts"
import { useEvents } from "../hooks/useEvents"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { useTrip } from "../hooks/useTrip"
import { type Airport, CategoryCategory, ExpenseType, PlaceIncludedEntity, PlaceSortingStrategy, UserRole } from "../types/CoreSwaggerTypes.ts"
import { InternalCategoryCategory } from "../types/InternalCategoryCategory.ts"

export default function TripPage() {
    const { tripId } = useParams()
    const { hasRole } = useAuth()
    const { t } = useTranslation()
    const { publishPhotosUploadingTriggeredEvent, publishPhotoReplacingTriggeredEvent } = useEvents()

    const { trip, removeTrip, moveTrip, loadTrip, updateTripName, removeTripHighlight, updateTripMainHighlight,
        createTripExpense, updateTripExpenseDescription, updateTripExpenseValue, updateTripNoteContent,
        removeTripExpense, createTripNote, removeTripNote, updateTripHighlightQualityAttributes, refreshTripHighlights } = useTrip(tripId)
    const { trips: candidateTrips } = useCandidateTrips()
    const { places } = useRegularPlaces({ tripId, include: [PlaceIncludedEntity.Categories, PlaceIncludedEntity.Dates, PlaceIncludedEntity.Notes], sort: PlaceSortingStrategy.ValueScore })
    const { candidatePlaces } = useCandidatePlaces({ tripId, include: [PlaceIncludedEntity.Categories, PlaceIncludedEntity.Dates, PlaceIncludedEntity.Notes], sort: PlaceSortingStrategy.ValueScore })
    const countryCategoriesMap = useCountryCategoriesMap()

    const tripPlaces = useMemo(() => trip?.isCandidate() ? candidatePlaces : places, [trip, places, candidatePlaces])
    const tripPlacesWithoutLayover = useMemo(() => trip && tripPlaces?.filter(place => !place.dates?.some(date => date?.layover)), [tripPlaces])

    const visitedCountriesMap = useMemo(() => new Map(tripPlacesWithoutLayover?.map(place => place.getCategory(CategoryCategory.Country))
        ?.filter(Boolean)?.map(category => [category.name, category])), [tripPlacesWithoutLayover])

    const attributes = {
        [t("trip.attribute.highlightsCount")]: trip?.highlights?.length
    }

    const getPlaceCategory = (place: Place) => {
        if (visitedCountriesMap.size > 1) {
            return visitedCountriesMap.get(place?.country)
        }
        return place?.getCategory(InternalCategoryCategory.MostSpecificWithMetadata)
    }
    const getAirportCategory = (airport: Airport) => countryCategoriesMap.get(airport.country)

    const handlePhotoCorrected = async (placeId: string, albumId: string, fileName: string, base64Data: string, photoId: string) => createPlaceAlbumPhoto(placeId, albumId, fileName, base64Data, photoId)
        .then(({ batchId }) => refreshPlaceAlbum(placeId, albumId, { batchId }))
        .then(_ => listPlaceAlbumPhotos(placeId, albumId))
        .then(photos => photos.find(photo => photo.id === photoId))

    return hasRole(UserRole.TripRead) && (
        <>
            <PageHeader
                name={trip ? trip.getFullName() : null}
                categories={[...visitedCountriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))}
                internalAttributes={hasRole(UserRole.TripEdit) && attributes}
                onHighlightsRefreshed={hasRole(UserRole.TripHighlightEdit) && places?.some(place => place.dates?.some(date => date.album)) && (highlightsCount => refreshTripHighlights(highlightsCount))}
                onNameChanged={hasRole(UserRole.TripEdit) && updateTripName}
                onRemoved={hasRole(UserRole.TripEdit) && removeTrip} />
            <HighlightCarouselAndPlaceMapAndFlightMapToggleToggle
                entity={trip}
                places={tripPlacesWithoutLayover}
                flights={trip && (trip.flights ?? []).filter(flight => flight.registration)}
                placeMainCategorySelector={getPlaceCategory}
                airportMainCategorySelector={getAirportCategory}
                onPhotoReplaced={hasRole(UserRole.PlaceAlbumEdit) && publishPhotoReplacingTriggeredEvent}
                onPhotoCorrected={hasRole(UserRole.PlaceAlbumEdit) && handlePhotoCorrected}
                onHighlightRemoved={hasRole(UserRole.TripHighlightEdit) && removeTripHighlight}
                onMainHighlightUpdated={hasRole(UserRole.TripEdit) && updateTripMainHighlight}
                onHighlightQualityAttributesUpdated={hasRole(UserRole.HighlightEdit) && updateTripHighlightQualityAttributes} />
            <StatisticsPanel statistics={trip && (trip.statistics ?? [])} />
            <TripCalendar
                trip={trip}
                places={tripPlaces}
                tripCandidates={candidateTrips}
                displayWarnings={hasRole(UserRole.PortalWarningRead)}
                onPhotosAdded={hasRole(UserRole.PlaceAlbumEdit) && !trip?.isCandidate() && publishPhotosUploadingTriggeredEvent}
                onNoteAdded={hasRole(UserRole.TripNoteEdit) && createTripNote}
                onNoteRemoved={hasRole(UserRole.TripNoteEdit) && removeTripNote}
                onTripMoved={hasRole(UserRole.TripEdit) && moveTrip}
                onTripLoaded={hasRole(UserRole.TripEdit) && loadTrip} />
            <PlaceTileGrid
                places={tripPlacesWithoutLayover?.filter(place => place.dates?.some(date => date?.start < Date.now() / 1000))}
                placeMainCategorySelector={getPlaceCategory} />
            {hasRole(UserRole.TripExpenseRead) && !trip?.isCandidate() && (
                <ExpenseSummary
                    expenses={trip && (trip.expenses ?? [])}
                    expenseCandidates={trip?.isPast() ? [] : [
                        ...(trip?.flights?.map(flight => ({ type: ExpenseType.Flight, description: `${flight.from?.shortName} - ${flight.to?.shortName}` })) ?? []),
                        ...(trip?.stays?.map(stay => ({ type: ExpenseType.Hotel, description: stay.name })) ?? [])
                    ]}
                    onExpenseCreated={hasRole(UserRole.TripExpenseEdit) && createTripExpense}
                    onExpenseDescriptionUpdated={hasRole(UserRole.TripExpenseEdit) && updateTripExpenseDescription}
                    onExpenseValueUpdated={hasRole(UserRole.TripExpenseEdit) && updateTripExpenseValue}
                    onExpenseRemoved={hasRole(UserRole.TripExpenseEdit) && removeTripExpense} />
            )}
            {hasRole(UserRole.TripNoteRead) && (
                <NoteCardGrid
                    rowSize={3}
                    notes={trip && (trip.notes ?? [])}
                    onNoteCreated={hasRole(UserRole.TripNoteEdit) && createTripNote}
                    onNoteContentUpdated={hasRole(UserRole.TripNoteEdit) && updateTripNoteContent}
                    onNoteRemoved={hasRole(UserRole.TripNoteEdit) && removeTripNote} />
            )}
            <TripNavigation
                trip={trip}
                canDisplayFutureTrips={hasRole(UserRole.PortalFutureRead)} />
        </>
    )
}
