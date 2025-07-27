import { useQuery, useQueryClient } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import Trip from "../model/trip"

export const useTrip = tripId => {
    const { getTrip, removeTrip, replaceTrip, updateTripStart, updateTripName, createTripHighlight, removeTripHighlight,
        updateTripMainHighlight, updateHighlightQualityAttributes, createTripExpense, removeTripExpense,
        updateTripExpenseDescription, updateTripExpenseValue, createTripNote, removeTripNote } = useApi()
    const { isAdmin } = useAuth()

    const queryClient = useQueryClient()

    const query = useQuery({
        queryKey: ["getTrip", tripId],
        queryFn: () => getTrip(tripId),
        enabled: !!tripId,
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 2
    })

    const setTrip = trip => queryClient.setQueryData(["getTrip", tripId], trip)
    const refetchTrip = _ => query.refetch()

    return {
        trip: query.data && new Trip(query.data),
        removeTrip: _ => removeTrip(tripId),
        loadTrip: candidateTripId => replaceTrip(tripId, candidateTripId).then(setTrip),
        moveTrip: days => updateTripStart(tripId, query.data.start + days * 86400).then(setTrip),
        updateTripName: name => updateTripName(tripId, name).then(setTrip),
        createTripHighlight: photoId => createTripHighlight(tripId, photoId).then(refetchTrip),
        removeTripHighlight: highlightId => removeTripHighlight(tripId, highlightId).then(refetchTrip),
        updateTripMainHighlight: highlightId => updateTripMainHighlight(tripId, highlightId).then(setTrip),
        updateTripHighlightQualityAttributes: (highlightId, composition, sky, shadows, circumstances, atmosphere) =>
            updateHighlightQualityAttributes(highlightId, composition, sky, shadows, circumstances, atmosphere).then(refetchTrip),
        createTripExpense: (type, description, value, currency, subscriptionId) => createTripExpense(tripId, type, description, value, currency, subscriptionId).then(refetchTrip),
        removeTripExpense: expenseId => removeTripExpense(tripId, expenseId).then(refetchTrip),
        updateTripExpenseDescription: (expenseId, description) => updateTripExpenseDescription(tripId, expenseId, description).then(refetchTrip),
        updateTripExpenseValue: (expenseId, value, currency) => updateTripExpenseValue(tripId, expenseId, value, currency).then(refetchTrip),
        createTripNote: name => createTripNote(tripId, name).then(refetchTrip),
        removeTripNote: noteId => removeTripNote(tripId, noteId).then(refetchTrip)
    }
}