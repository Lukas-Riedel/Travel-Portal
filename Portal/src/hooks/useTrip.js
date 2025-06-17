import { useQuery, useQueryClient } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"
import Trip from "../model/trip"

export const useTrip = (tripId) => {
    const api = useApi()
    const { isAdmin } = useAuth()

    const queryClient = useQueryClient()

    const query = useQuery({
        queryKey: ["getTrip", tripId],
        queryFn: () => api.getTrip(tripId),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 2,
    })

    const setTrip = trip => queryClient.setQueryData(["getTrip", tripId], trip)
    const refetchTrip = _ => query.refetch()

    return {
        trip: query.data && new Trip(query.data),
        removeTrip: _ => api.removeTrip(tripId),
        loadTrip: candidateTripId => api.replaceTrip(tripId, candidateTripId).then(setTrip),
        moveTrip: days => api.updateTripStart(tripId, query.data.start + days * 86400).then(setTrip),
        updateTripName: name => api.updateTripName(tripId, name).then(setTrip),
        removeTripHighlight: highlightId => api.removeTripHighlight(tripId, highlightId).then(refetchTrip),
        updateTripMainHighlight: highlightId => api.updateTripMainHighlight(tripId, highlightId).then(setTrip),
        createTripExpense: (type, description, value, currency, subscriptionId) => api.createTripExpense(tripId, type, description, value, currency, subscriptionId).then(refetchTrip),
        removeTripExpense: expenseId => api.removeTripExpense(tripId, expenseId).then(refetchTrip),
        updateTripExpenseDescription: (expenseId, description) => api.updateTripExpenseDescription(tripId, expenseId, description).then(refetchTrip),
        updateTripExpenseValue: (expenseId, value, currency) => api.updateTripExpenseValue(tripId, expenseId, value, currency).then(refetchTrip),
        createTripNote: name => api.createTripNote(tripId, name).then(refetchTrip),
        removeTripNote: noteId => api.removeTripNote(tripId, noteId).then(refetchTrip)
    }
}