import { useParams } from "react-router-dom"
import { useTrip } from "../hooks/useTrip"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import PageHeader from "../components/PageHeader"
import HighlightCarouselAndPlaceMapAndFlightMapToggleToggle from "../components/HighlightCarouselAndPlaceMapAndFlightMapToggleToggle"
import StatisticsPanel from "../components/StatisticsPanel"
import PlaceTileGrid from "../components/PlaceTileGrid"
import { useMemo } from "react"
import TripCalendar from "../components/TripCalendar"
import TripNavigation from "../components/TripNavigation"
import { useCandidatePlaces } from "../hooks/useCandidatePlaces"
import ExpenseSummary from "../components/ExpenseSummary"
import { useAuth } from "../contexts/AuthContext"
import { useCandidateTrips } from "../hooks/useCandidateTrips"
import { useEvents } from "../hooks/useEvents"
import { createPlaceAlbumPhoto, refreshPlaceAlbum } from "../clients/coreClient"
import NoteCardGrid from "../components/NoteCardGrid.jsx"
import { UserRole } from "../types/CoreSwaggerTypes.ts"
import { useCategories } from "../hooks/useCategories.ts"

export default function TripPage() {
    const { hasRole } = useAuth()
    const { publishPhotosUploadingTriggeredEvent, publishPhotoReplacingTriggeredEvent } = useEvents()

    const { tripId } = useParams()

    const { trip, removeTrip, moveTrip, loadTrip, updateTripName, removeTripHighlight, updateTripMainHighlight,
        createTripExpense, updateTripExpenseDescription, updateTripExpenseValue, updateTripNoteContent,
        removeTripExpense, createTripNote, removeTripNote, updateTripHighlightQualityAttributes, refreshTripHighlights } = useTrip(tripId)
    const { trips: candidateTrips } = useCandidateTrips()
    const { places } = useRegularPlaces({ tripId, include: ["categories", "dates", "notes"], sort: "-score" })
    const { candidatePlaces } = useCandidatePlaces({ tripId, include: ["categories", "dates", "notes"], sort: "-score" })
    const countryCategories = useCategories({ categories: ["country"] })

    const tripPlaces = useMemo(() => trip?.isCandidate() ? candidatePlaces : places, [trip, places, candidatePlaces])
    const tripPlacesWithoutLayover = useMemo(() => trip && tripPlaces?.filter(place => !place.dates?.some(date => date?.layover)), [tripPlaces])

    const countryCategoriesMap = useMemo(() => {
        return new Map(countryCategories?.map(category => [category.name, category]))
    }, [countryCategories])
    const visitedCountriesMap = useMemo(() => new Map(tripPlacesWithoutLayover?.map(place => place.getCategory("country"))
        ?.filter(Boolean)?.map(category => [category.name, category])), [tripPlacesWithoutLayover])

    const getPlaceCategory = place => {
        if (visitedCountriesMap.size > 1) {
            return visitedCountriesMap.get(place?.country)
        }
        return place?.getCategory("mostSpecificWithMetadata")
    }
    const getAirportCategory = airport => countryCategoriesMap.get(airport.country)

    const handlePhotoCorrected = async (placeId, albumId, fileName, data, replacedPhotoId) => createPlaceAlbumPhoto(placeId, albumId, fileName, data, replacedPhotoId).then(({ batchId }) => refreshPlaceAlbum(placeId, albumId, { batchId }))

    return hasRole(UserRole.TripRead) && (
        <>
            <PageHeader
                name={trip && trip.getFullName()}
                categories={[...visitedCountriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))}
                internalAttributes={hasRole(UserRole.TripEdit) && { "Počet highlightů": trip?.highlights?.length }}
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
                displayCopyItineraryButton={hasRole(UserRole.TripEdit)}
                onPhotosAdded={hasRole(UserRole.PlaceAlbumEdit) && !trip?.isCandidate() && publishPhotosUploadingTriggeredEvent}
                onNoteAdded={hasRole(UserRole.TripNoteEdit) && createTripNote}
                onNoteRemoved={hasRole(UserRole.TripNoteEdit) && removeTripNote}
                onTripMoved={hasRole(UserRole.TripEdit) && moveTrip}
                onTripLoaded={hasRole(UserRole.TripEdit) && loadTrip} />
            <PlaceTileGrid
                places={tripPlacesWithoutLayover?.filter(place => place.dates?.some(date => date?.start < Date.now() / 1000))}
                placeMainCategorySelector={getPlaceCategory} />
            {!trip?.isCandidate() && (
                <ExpenseSummary
                    expenses={trip && (trip.expenses ?? [])}
                    expenseCandidates={trip?.isPast() ? [] : [
                        ...(trip?.flights?.map(flight => ({ type: "flight", description: `${flight.from?.shortName} - ${flight.to?.shortName}` })) ?? []),
                        ...(trip?.stays?.map(stay => ({ type: "hotel", description: stay.name })) ?? [])
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
            <TripNavigation trip={trip} />
        </>
    )
}