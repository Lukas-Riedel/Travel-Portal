import {
    getTrip, removeTrip, replaceTrip, updateTripStart, updateTripName, createTripHighlight, removeTripHighlight,
    updateTripMainHighlight, updateHighlightQualityAttributes, createTripExpense, removeTripExpense,
    updateTripExpenseDescription, updateTripExpenseValue, createTripNote, removeTripNote,
    updateTripNoteContent, refreshTripHighlights,
    createTripTask,
    updateTripTaskDescription,
    updateTripTaskPriority,
    removeTripTask
} from "../clients/coreClient.ts"
import { Trip } from "../classes/Trip.ts"
import { ONE_HOUR_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"
import type { ExpenseType, TaskPriority } from "../types/CoreSwaggerTypes.ts"
import type { UseTripResult } from "../types/UseTripResult.ts"

export const useTrip = (tripId?: string): UseTripResult => {
    const { response, setResponse, refetchResponse } = useQuery({
        queryKey: ["getTrip", tripId],
        queryFn: () => getTrip(tripId),
        enabled: !!tripId,
        staleTime: ONE_HOUR_SECONDS * 1000
    })

    return {
        trip: response && new Trip(response),
        removeTrip: () => removeTrip(tripId),
        loadTrip: (candidateTripId: string) => replaceTrip(tripId, candidateTripId).then(setResponse),
        moveTrip: (start: number) => updateTripStart(tripId, start).then(setResponse),
        updateTripName: (name: string) => updateTripName(tripId, name).then(setResponse),
        createTripHighlight: (photoId: string) => createTripHighlight(tripId, photoId).then(refetchResponse),
        removeTripHighlight: (highlightId: string) => removeTripHighlight(tripId, highlightId).then(refetchResponse),
        updateTripMainHighlight: (highlightId: string) => updateTripMainHighlight(tripId, highlightId).then(setResponse),
        updateTripHighlightQualityAttributes: (highlightId: string, composition: number | null, sky: number | null, shadows: number | null, circumstances: number | null, atmosphere: number | null) =>
            updateHighlightQualityAttributes(highlightId, composition, sky, shadows, circumstances, atmosphere).then(refetchResponse),
        createTripExpense: (type: ExpenseType, description: string, value: number, currency: string, subscriptionId?: string) => createTripExpense(tripId, type, description, value, currency, subscriptionId).then(refetchResponse),
        removeTripExpense: (expenseId: string) => removeTripExpense(tripId, expenseId).then(refetchResponse),
        updateTripExpenseDescription: (expenseId: string, description: string) => updateTripExpenseDescription(tripId, expenseId, description).then(refetchResponse),
        updateTripExpenseValue: (expenseId: string, value: number, currency: string) => updateTripExpenseValue(tripId, expenseId, value, currency).then(refetchResponse),
        createTripTask: (tripId: string, description: string, priority: TaskPriority, deadline?: number) => createTripTask(tripId, description, priority, deadline).then(refetchResponse),
        updateTripTaskDescription: (tripId: string, taskId: string, description: string) => updateTripTaskDescription(tripId, taskId, description).then(refetchResponse),
        updateTripTaskPriority: (tripId: string, taskId: string, priority: TaskPriority) => updateTripTaskPriority(tripId, taskId, priority).then(refetchResponse),
        removeTripTask: (tripId: string, taskId: string) => removeTripTask(tripId, taskId).then(refetchResponse),
        createTripNote: (name: string) => createTripNote(tripId, name).then(refetchResponse),
        updateTripNoteContent: (noteId: string, content: string) => updateTripNoteContent(tripId, noteId, content).then(refetchResponse),
        removeTripNote: (noteId: string) => removeTripNote(tripId, noteId).then(refetchResponse),
        refreshTripHighlights: (count: number) => refreshTripHighlights(tripId, count).then(refetchResponse)
    }
}