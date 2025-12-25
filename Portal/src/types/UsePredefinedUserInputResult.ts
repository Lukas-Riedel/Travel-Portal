import type { Place } from "../classes/Place.ts"
import type { Airline, Album, Document, Expense, Flight, Highlight, Label, Note, Subscription, Voucher } from "./CoreSwaggerTypes.ts"
import type { Highlightable } from "./Highlightable.ts"

export interface UsePredefinedUserInputResult {
    showRemoveDocumentToast: (document: Document, removeDocument: () => Promise<void>) => Promise<boolean>
    showUpdateConfigurationEntryToast: (updateConfigurationEntry: () => Promise<Record<string, any>>) => Promise<boolean>
    showRemovePlaceToast: (place: Place, removePlace: () => Promise<void>) => Promise<boolean>
    showRefreshAlbumToast: (refreshAlbum: () => Promise<Album>) => Promise<boolean>
    showRemoveAlbumToast: (removeAlbum: () => Promise<void>) => Promise<boolean>
    showUpdateAlbumMainPhotoToast: (updateAlbumMainPhoto: () => Promise<Album>) => Promise<boolean>
    showCreateExpenseToast: (subscriptions: Subscription[], vouchers: Voucher[], createExpense: (subscriptionId?: string) => Promise<Expense>, updateVoucherValue: (voucherId: string, value: number) => Promise<Voucher>, removeVoucher: (voucherId: string) => Promise<void>) => Promise<boolean>
    showUpdateExpenseValueToast: (expense: Expense, currencies: string[], updateExpenseValue: (value: number, currency: string) => Promise<Expense>) => Promise<boolean>
    showUpdateExpenseDescriptionToast: (expense: Expense, updateExpenseDescription: (description: string) => Promise<Expense>) => Promise<boolean>
    showRemoveExpenseToast: (removeExpense: () => Promise<void>) => Promise<boolean>
    showCreateNoteToast: (createNote: (() => Promise<Note>) | ((content: string) => Promise<Note>)) => Promise<boolean>
    showRemoveNoteToast: (removeNote: () => Promise<void>) => Promise<boolean>
    showCreateAirlineToast: (createAirline: (name: string) => Promise<Airline>) => Promise<boolean>
    showUpdateAirlineToast: (airline: Airline, updateAirlineName: (name: string) => Promise<Airline>, updateAirlineLogo: (logo: string) => Promise<Airline>, removeAirlineCode: (code: string) => Promise<void>) => Promise<boolean>
    showRemoveAirlineToast: (airline: Airline, removeAirline: () => Promise<void>) => Promise<boolean>
    showRemovePhotoToast: (removePhoto: () => Promise<void>) => Promise<boolean>
    showLogFlightToast: (logFlight: () => Promise<Flight>) => Promise<boolean>
    showCreateHighlightToast: (createHighlight: () => Promise<Highlight>) => Promise<boolean>
    showUpdateMainHighlightToast: <T extends Highlightable> (updateMainHighlight: () => Promise<T>) => Promise<boolean>
    showUpdateHighlightToast: (updateHighlight: () => Promise<Highlight>) => Promise<boolean>
    showRemoveHighlightToast: (removeHighlight: () => Promise<void>) => Promise<boolean>
    showAssignLabelToast: (label: Label, createLabel: () => Promise<Label>) => Promise<boolean>
    showUnassignLabelToast: (label: Label, removeLabel: () => Promise<void>) => Promise<boolean>
}