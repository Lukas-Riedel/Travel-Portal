import type { Trip } from "../classes/Trip.ts"
import type { Expense, ExpenseType, Highlight, Note, TaskPriority, Task, ExpenseCurrency } from "./CoreSwaggerTypes.ts"

export interface UseTripResult {
    trip?: Trip,
    removeTrip: () => Promise<void>
    loadTrip: (candidateTripId: string) => Promise<Trip>
    moveTrip: (start: number) => Promise<Trip>
    updateTripName: (name: string) => Promise<Trip>
    createTripHighlight: (photoId: string) => Promise<Highlight>
    removeTripHighlight: (highlightId: string) => Promise<void>
    updateTripMainHighlight: (highlightId: string) => Promise<Trip>
    updateTripHighlightQualityAttributes: (highlightId: string, composition: number | null, sky: number | null, shadows: number | null, circumstances: number | null, atmosphere: number | null) => Promise<Highlight>
    createTripExpense: (type: ExpenseType, description: string, value: number, currency: ExpenseCurrency, subscriptionId?: string) => Promise<Expense>
    removeTripExpense: (expenseId: string) => Promise<void>
    updateTripExpenseDescription: (expenseId: string, description: string) => Promise<Expense>
    updateTripExpenseValue: (expenseId: string, value: number, currency: ExpenseCurrency) => Promise<Expense>
    createTripTask: (tripId: string, description: string, priority: TaskPriority, deadline?: number) => Promise<Task>
    updateTripTaskDescription: (tripId: string, taskId: string, description: string) => Promise<Task>
    updateTripTaskPriority: (tripId: string, taskId: string, priority: TaskPriority) => Promise<Task>
    removeTripTask: (tripId: string, taskId: string) => Promise<void>
    createTripNote: (name: string) => Promise<Note>
    updateTripNoteContent: (noteId: string, content: string) => Promise<Note>
    removeTripNote: (noteId: string) => Promise<void>
    refreshTripHighlights: (count: number) => Promise<Highlight[]>
}