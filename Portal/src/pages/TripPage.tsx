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

    const tripPlaces = trip?.isCandidate() ? candidatePlaces : places
    const tripPlacesWithoutLayover = trip && tripPlaces?.filter(place => !place.dates?.some(date => date?.layover))

    const visitedCountriesMap = new Map(tripPlacesWithoutLayover?.map(place => place.getCategory(CategoryCategory.Country))
        ?.filter((c): c is NonNullable<typeof c> => c != null)?.map(category => [category.name, category]))

    const attributes: Record<string, string | number | undefined> = {
        [t("trip.attribute.highlightsCount")]: trip?.highlights?.length
    }

    const getPlaceCategory = (place: Place): ReturnType<typeof place.getCategory> => {
        if (visitedCountriesMap.size > 1) {
            return visitedCountriesMap.get(place?.country) ?? null
        }
        return place?.getCategory(InternalCategoryCategory.MostSpecificWithMetadata) ?? null
    }
    const getAirportCategory = (airport: Airport) => countryCategoriesMap?.get(airport.country ?? "") ?? null

    const handlePhotoCorrected = async (placeId: string, albumId: string, fileName: string, base64Data: string, photoId: string) => createPlaceAlbumPhoto(placeId, albumId, fileName, base64Data, photoId)
        .then(({ batchId }) => refreshPlaceAlbum(placeId, albumId, { batchId }))
        .then(_ => listPlaceAlbumPhotos(placeId, albumId))
        .then(photos => photos.find(photo => photo.id === photoId)!)

    return hasRole(UserRole.TripRead) && (
        <>
            <PageHeader
                name={trip ? trip.getFullName() : null}
                categories={[...visitedCountriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))}
                internalAttributes={hasRole(UserRole.TripEdit) ? attributes : undefined}
                onHighlightsRefreshed={hasRole(UserRole.TripHighlightEdit) && places?.some(place => place.dates?.some(date => date.album)) ? (highlightsCount => refreshTripHighlights(highlightsCount)) : undefined}
                onNameChanged={hasRole(UserRole.TripEdit) ? updateTripName : undefined}
                onRemoved={hasRole(UserRole.TripEdit) ? removeTrip : undefined} />
            <HighlightCarouselAndPlaceMapAndFlightMapToggleToggle
                entity={trip ?? null}
                places={tripPlacesWithoutLayover ?? null}
                flights={trip ? (trip.flights ?? []).filter(flight => flight.registration) : null}
                placeMainCategorySelector={getPlaceCategory}
                airportMainCategorySelector={getAirportCategory}
                onPhotoReplaced={hasRole(UserRole.PlaceAlbumEdit) ? publishPhotoReplacingTriggeredEvent : undefined}
                onPhotoCorrected={hasRole(UserRole.PlaceAlbumEdit) ? handlePhotoCorrected : undefined}
                onHighlightRemoved={hasRole(UserRole.TripHighlightEdit) ? removeTripHighlight : undefined}
                onMainHighlightUpdated={hasRole(UserRole.TripEdit) ? updateTripMainHighlight : undefined}
                onHighlightQualityAttributesUpdated={hasRole(UserRole.HighlightEdit) ? updateTripHighlightQualityAttributes : undefined} />
            <StatisticsPanel statistics={trip ? (trip.statistics ?? []) : null} />
            <TripCalendar
                trip={trip ?? null}
                places={tripPlaces ?? null}
                tripCandidates={candidateTrips}
                displayWarnings={hasRole(UserRole.PortalWarningRead)}
                onPhotosAdded={hasRole(UserRole.PlaceAlbumEdit) && !trip?.isCandidate() ? publishPhotosUploadingTriggeredEvent : undefined}
                onNoteAdded={hasRole(UserRole.TripNoteEdit) ? createTripNote : undefined}
                onNoteRemoved={hasRole(UserRole.TripNoteEdit) ? removeTripNote : undefined}
                onTripMoved={hasRole(UserRole.TripEdit) ? moveTrip : undefined}
                onTripLoaded={hasRole(UserRole.TripEdit) ? loadTrip : undefined} />
            <PlaceTileGrid
                places={tripPlacesWithoutLayover?.filter(place => place.dates?.some(date => date?.start < Date.now() / 1000)) ?? null}
                placeMainCategorySelector={getPlaceCategory} />
            {hasRole(UserRole.TripExpenseRead) && !trip?.isCandidate() && (
                <ExpenseSummary
                    expenses={trip ? (trip.expenses ?? []) : null}
                    expenseCandidates={trip?.isPast() ? [] : [
                        ...(trip?.flights?.map(flight => ({ type: ExpenseType.Flight, description: `${flight.from?.shortName} - ${flight.to?.shortName}` })) ?? []),
                        ...(trip?.stays?.map(stay => ({ type: ExpenseType.Hotel, description: stay.name })) ?? [])
                    ]}
                    onExpenseCreated={hasRole(UserRole.TripExpenseEdit) ? createTripExpense : undefined}
                    onExpenseDescriptionUpdated={hasRole(UserRole.TripExpenseEdit) ? updateTripExpenseDescription : undefined}
                    onExpenseValueUpdated={hasRole(UserRole.TripExpenseEdit) ? updateTripExpenseValue : undefined}
                    onExpenseRemoved={hasRole(UserRole.TripExpenseEdit) ? removeTripExpense : undefined} />
            )}
            {hasRole(UserRole.TripNoteRead) && (
                <NoteCardGrid
                    rowSize={3}
                    notes={trip ? (trip.notes ?? []) : null}
                    onNoteCreated={hasRole(UserRole.TripNoteEdit) ? createTripNote : undefined}
                    onNoteContentUpdated={hasRole(UserRole.TripNoteEdit) ? updateTripNoteContent : undefined}
                    onNoteRemoved={hasRole(UserRole.TripNoteEdit) ? removeTripNote : undefined} />
            )}
            <TripNavigation
                trip={trip ?? null}
                canDisplayFutureTrips={hasRole(UserRole.PortalFutureRead)} />
        </>
    )
}
