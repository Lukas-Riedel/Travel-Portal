import type { Trip } from "../classes/Trip.ts"
import type { ExpenseType } from "./CoreSwaggerTypes.ts"

export interface UseTripResult {
    trip?: Trip,
    removeTrip: () => Promise<void>
    loadTrip: (candidateTripId: string) => Promise<void>
    moveTrip: (start: number) => Promise<void>
    updateTripName: (name: string) => Promise<void>
    createTripHighlight: (photoId: string) => Promise<void>
    removeTripHighlight: (highlightId: string) => Promise<void>
    updateTripMainHighlight: (highlightId: string) => Promise<void>
    updateTripHighlightQualityAttributes: (highlightId: string, composition?: number, sky?: number, shadows?: number, circumstances?: number, atmosphere?: number) => Promise<void>
    createTripExpense: (type: ExpenseType, description: string, value: number, currency: string, subscriptionId?: string) => Promise<void>
    removeTripExpense: (expenseId: string) => Promise<void>
    updateTripExpenseDescription: (expenseId: string, description: string) => Promise<void>
    updateTripExpenseValue: (expenseId: string, value: number, currency: string) => Promise<void>
    createTripNote: (name: string) => Promise<void>
    removeTripNote: (noteId: string) => Promise<void>
}