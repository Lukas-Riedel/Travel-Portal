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
import { useAuth } from "../contexts/AuthContext"
import { useCandidateTrips } from "../hooks/useCandidateTrips"
import { useEvents } from "../hooks/useEvents"
import { createPlaceAlbumPhoto, refreshPlaceAlbum } from "../clients/coreClient"
import NoteCardGrid from "../components/NoteCardGrid.jsx"
import { HighlightType } from "../types/CoreSwaggerTypes.ts"

export default function TripPage() {
    const { isAdmin } = useAuth()
    const { publishPhotosUploadingTriggeredEvent, publishPhotoReplacingTriggeredEvent, publishHighlightsSelectingTriggeredEvent } = useEvents()

    const { tripId } = useParams()

    const { trip, removeTrip, moveTrip, loadTrip, updateTripName, removeTripHighlight, updateTripMainHighlight,
        createTripExpense, updateTripExpenseDescription, updateTripExpenseValue, updateTripNoteContent,
        removeTripExpense, createTripNote, removeTripNote, updateTripHighlightQualityAttributes } = useTrip(tripId)
    const { trips: candidateTrips } = useCandidateTrips()
    const { places } = useRegularPlaces({ tripId, include: ["categories", "dates"], sort: "-score" })
    const { candidatePlaces } = useCandidatePlaces({ tripId, include: ["categories", "dates"], sort: "-score" })
    const tripPlaces = useMemo(() => trip?.isCandidate() ? candidatePlaces : places, [trip, places, candidatePlaces])
    const tripPlacesWithoutLayover = useMemo(() => trip && tripPlaces?.filter(place => !place.dates?.some(date => date?.layover)), [tripPlaces])
    const countryCategoriesMap = useMemo(() => new Map(tripPlacesWithoutLayover?.map(place => place.getCategory("country"))
        ?.filter(Boolean)?.map(category => [category.name, category])), [tripPlacesWithoutLayover])

    const getPlaceCategory = place => {
        if (countryCategoriesMap.size > 1) {
            return countryCategoriesMap.get(place?.country)
        }
        return place?.getCategory("mostSpecificWithMetadata")
    }

    const handlePhotoCorrected = async (placeId, albumId, fileName, data, replacedPhotoId) => createPlaceAlbumPhoto(placeId, albumId, fileName, data, replacedPhotoId).then(() => refreshPlaceAlbum(placeId, albumId))

    return (
        <>
            <PageHeader
                name={trip && trip.getFullName()}
                categories={[...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))}
                internalAttributes={{ "Počet highlightů": trip?.highlights?.length }}
                onHighlightsSelectingTriggered={places?.some(place => place.dates?.some(date => date.album)) && (highlightsCount => publishHighlightsSelectingTriggeredEvent(HighlightType.Trip, tripId, highlightsCount, true))}
                onNameChanged={updateTripName}
                onRemoved={removeTrip} />
            <HighlightCarouselAndPlaceMapToggle
                entity={trip}
                places={tripPlacesWithoutLayover}
                placeMainCategorySelector={getPlaceCategory}
                onPhotoReplaced={publishPhotoReplacingTriggeredEvent}
                onPhotoCorrected={handlePhotoCorrected}
                onHighlightRemoved={removeTripHighlight}
                onMainHighlightUpdated={updateTripMainHighlight}
                onHighlightQualityAttributesUpdated={updateTripHighlightQualityAttributes} />
            <StatisticsPanel statistics={trip && (trip.statistics ?? [])} />
            <TripCalendar
                trip={trip}
                places={tripPlaces}
                tripCandidates={candidateTrips}
                onPhotosAdded={!trip?.isCandidate() && publishPhotosUploadingTriggeredEvent}
                onNoteAdded={createTripNote}
                onNoteRemoved={removeTripNote}
                onTripMoved={moveTrip}
                onTripLoaded={loadTrip} />
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
                    onExpenseCreated={createTripExpense}
                    onExpenseDescriptionUpdated={updateTripExpenseDescription}
                    onExpenseValueUpdated={updateTripExpenseValue}
                    onExpenseRemoved={removeTripExpense} />
            )}
            {isAdmin && (
                <NoteCardGrid
                    notes={trip && (trip.notes ?? [])}
                    onNoteCreated={createTripNote}
                    onNoteContentUpdated={updateTripNoteContent}
                    onNoteRemoved={removeTripNote} />
            )}
            <TripNavigation trip={trip} />
        </>
    )
}