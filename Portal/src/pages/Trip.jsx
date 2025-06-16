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
import { useApi } from "../hooks/useApi"
import ExpenseSummary from "../components/ExpenseSummary"
import NoteBar from "../components/NoteBar"
import { useAuth } from "../contexts/AuthContext"
import { useCandidateTrips } from "../hooks/useCandidateTrips"

export default function Trip() {
    const { isAdmin } = useAuth()
    const api = useApi()

    const { tripId } = useParams()

    const { trip, removeTrip, moveTrip, loadTrip, updateTripName, removeTripHighlight, updateTripMainHighlight,
        createTripExpense, updateTripExpenseDescription, updateTripExpenseValue,
        removeTripExpense, createTripNote, removeTripNote } = useTrip(tripId)
    const candidateTrips = useCandidateTrips()
    const regularPlaces = useRegularPlaces({ tripId, include: "CATEGORIES,DATES", sort: "score" })
    const candidatePlaces = useCandidatePlaces({ tripId, include: "CATEGORIES,DATES", sort: "score" })
    const tripPlaces = useMemo(() => trip?.isCandidate() ? candidatePlaces : regularPlaces, [trip, regularPlaces, candidatePlaces])
    const tripPlacesWithoutLayover = useMemo(() => tripPlaces?.filter(place => !place.dates?.some(date => date?.layover)), [tripPlaces])
    const countryCategoriesMap = useMemo(() => new Map(tripPlacesWithoutLayover?.map(place => place.getCategory("COUNTRY"))
        ?.filter(category => category)?.map(category => [category.name, category])), [tripPlaces])

    const getPlaceCategory = place => {
        if (countryCategoriesMap.size > 1) {
            return countryCategoriesMap.get(place?.country)
        }
        return place?.getCategory("MOST_SPECIFIC_WITH_METADATA")
    }

    return (
        <>
            <PageHeader
                name={trip && trip.getFullName()}
                categories={[...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))}
                loadCandidates={candidateTrips?.map(candidateTrip => ({ id: candidateTrip.id, name: candidateTrip.name }))}
                onNameChanged={updateTripName}
                onMoved={moveTrip}
                onLoaded={loadTrip}
                onRemoved={removeTrip} />
            <HighlightCarouselAndPlaceMapToggle
                entity={trip}
                places={tripPlacesWithoutLayover}
                placeMainCategorySelector={getPlaceCategory}
                onHighlightRemoved={removeTripHighlight}
                onMainHighlightUpdated={updateTripMainHighlight} />
            <StatisticsPanel statistics={trip?.statistics} />
            <TripCalendar
                trip={trip}
                places={tripPlaces}
                onPhotosAdded={trip?.isCandidate() ? undefined : (placeId, albumId, timestamp, path, mainPhotoPosition) =>
                    api.createEvent("PhotosUploading", { placeId, albumId, timestamp, path, mainPhotoPosition })} />
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
                    notes={trip?.notes}
                    onNoteCreated={createTripNote}
                    onNoteRemoved={removeTripNote} />
            )}
            {!trip?.isDayTrips() && (
                <TripNavigation trip={trip} />)}
        </>
    )
}