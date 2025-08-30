import { useParams } from "react-router-dom"
import { useTrip } from "../hooks/useTrip"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import PageHeader from "../components/PageHeader"
import HighlightCarouselAndPlaceMapToggle from "../components/HighlightCarouselAndPlaceMapToggle"
import StatisticsPanel from "../components/StatisticsPanel"
import PlaceTileGrid from "../components/PlaceTileGrid"
import { useMemo } from "react"
import TripCalendar from "../components/TripCalendar"
import TripNavigation from "../components/TripNavigation"
import { useCandidatePlaces } from "../hooks/useCandidatePlaces"
import ExpenseSummary from "../components/ExpenseSummary"
import NoteBar from "../components/NoteBar"
import { useAuth } from "../contexts/AuthContext"
import { useCandidateTrips } from "../hooks/useCandidateTrips"
import { useEvents } from "../hooks/useEvents"

export default function TripPage() {
    const { isAdmin } = useAuth()
    const { publishPhotosUploadingTriggeredEvent, publishPhotoReplacingTriggeredEvent } = useEvents()

    const { tripId } = useParams()

    const { trip, removeTrip, moveTrip, loadTrip, updateTripName, removeTripHighlight, updateTripMainHighlight,
        createTripExpense, updateTripExpenseDescription, updateTripExpenseValue,
        removeTripExpense, createTripNote, removeTripNote, updateTripHighlightQualityAttributes } = useTrip(tripId)
    const { candidateTrips } = useCandidateTrips()
    const regularPlaces = useRegularPlaces({ tripId, include: "categories,dates", sort: "-score" })
    const { candidatePlaces } = useCandidatePlaces({ tripId, include: "categories,dates", sort: "-score" })
    const tripPlaces = useMemo(() => trip?.isCandidate() ? candidatePlaces : regularPlaces, [trip, regularPlaces, candidatePlaces])
    const tripPlacesWithoutLayover = useMemo(() => trip && tripPlaces?.filter(place => !place.dates?.some(date => date?.layover)), [tripPlaces])
    const countryCategoriesMap = useMemo(() => new Map(tripPlacesWithoutLayover?.map(place => place.getCategory("country"))
        ?.filter(Boolean)?.map(category => [category.name, category])), [tripPlacesWithoutLayover])

    const getPlaceCategory = place => {
        if (countryCategoriesMap.size > 1) {
            return countryCategoriesMap.get(place?.country)
        }
        return place?.getCategory("mostSpecificWithMetadata")
    }
    
    return (
        <>
            <PageHeader
                name={trip && trip.getFullName()}
                categories={[...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))}
                onNameChanged={updateTripName}
                onRemoved={removeTrip} />
            <HighlightCarouselAndPlaceMapToggle
                entity={trip}
                places={tripPlacesWithoutLayover}
                placeMainCategorySelector={getPlaceCategory}
                onPhotoReplaced={publishPhotoReplacingTriggeredEvent}
                onHighlightRemoved={removeTripHighlight}
                onMainHighlightUpdated={updateTripMainHighlight}
                onHighlightQualityAttributesUpdated={updateTripHighlightQualityAttributes} />
            {trip?.start < Date.now() / 1000 && (
                <StatisticsPanel statistics={trip?.statistics} />
            )}
            <TripCalendar
                trip={trip}
                places={tripPlaces}
                tripCandidates={candidateTrips}
                onPhotosAdded={!trip?.isCandidate() && publishPhotosUploadingTriggeredEvent}
                onTripMoved={moveTrip}
                onTripLoaded={loadTrip} />
            <PlaceTileGrid
                places={tripPlacesWithoutLayover?.filter(place => place.dates?.some(date => date?.start < Date.now() / 1000))}
                placeMainCategorySelector={getPlaceCategory} />
            {!trip?.isDayTrips() && !trip?.isCandidate() && (
                <ExpenseSummary
                    expenses={trip?.expenses}
                    expenseCandidates={trip?.isPast() ? [] : [
                        ...(trip?.flights?.map(flight => ({ type: "FLIGHT", description: `${flight.from?.name} - ${flight.to?.name}` })) ?? []),
                        ...(trip?.stays?.map(stay => ({ type: "HOTEL", description: stay.name })) ?? [])
                    ]}
                    onExpenseCreated={createTripExpense}
                    onExpenseDescriptionUpdated={updateTripExpenseDescription}
                    onExpenseValueUpdated={updateTripExpenseValue}
                    onExpenseRemoved={removeTripExpense} />
            )}
            {isAdmin && (
                <NoteBar
                    notes={trip && (trip.notes ?? [])}
                    onNoteCreated={createTripNote}
                    onNoteRemoved={removeTripNote} />
            )}
            {!trip?.isDayTrips() && (
                <TripNavigation trip={trip} />)}
        </>
    )
}