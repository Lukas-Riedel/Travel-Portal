import type { Trip } from "../classes/Trip.ts"
import type { Expense, ExpenseType, Highlight, Note } from "./CoreSwaggerTypes.ts"

export interface UseTripResult {
    trip?: Trip,
    removeTrip: () => Promise<void>
    loadTrip: (candidateTripId: string) => Promise<Trip>
    moveTrip: (start: number) => Promise<Trip>
    updateTripName: (name: string) => Promise<Trip>
    createTripHighlight: (photoId: string) => Promise<Highlight>
    removeTripHighlight: (highlightId: string) => Promise<void>
    updateTripMainHighlight: (highlightId: string) => Promise<Trip>
    updateTripHighlightQualityAttributes: (highlightId: string, composition: number, sky: number, shadows: number, circumstances: number, atmosphere: number) => Promise<Highlight>
    createTripExpense: (type: ExpenseType, description: string, value: number, currency: string, subscriptionId?: string) => Promise<Expense>
    removeTripExpense: (expenseId: string) => Promise<void>
    updateTripExpenseDescription: (expenseId: string, description: string) => Promise<Expense>
    updateTripExpenseValue: (expenseId: string, value: number, currency: string) => Promise<Expense>
    createTripNote: (name: string) => Promise<Note>
    updateTripNoteContent: (noteId: string, content: string) => Promise<Note>
    removeTripNote: (noteId: string) => Promise<void>
    refreshTripHighlights: (count: number) => Promise<Highlight[]>
}