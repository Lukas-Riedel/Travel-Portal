import type { Place } from "../classes/Place.ts"
import type { Airline, Album, Document, Expense, Note, Subscription, Voucher } from "./CoreSwaggerTypes.ts"

export interface UsePredefinedUserInputResult {
    showRemoveDocumentToast: (document: Document, removeDocument: () => Promise<void>) => Promise<boolean>
    showUpdateConfigurationEntryToast: (updateConfigurationEntry: () => Promise<Record<string, any>>) => Promise<boolean>
    showRemovePlaceToast: (place: Place, removePlace: () => Promise<void>) => Promise<boolean>
    showRefreshAlbumToast: (refreshAlbum: () => Promise<Album>) => Promise<boolean>
    showUpdateAlbumMainPhotoToast: (updateAlbumMainPhoto: () => Promise<Album>) => Promise<boolean>
    showCreateExpenseToast: (subscriptions: Subscription[], vouchers: Voucher[], createExpense: (subscriptionId?: string) => Promise<Expense>, updateVoucherValue: (voucherId: string, value: number) => Promise<Voucher>, removeVoucher: (voucherId: string) => Promise<void>) => Promise<boolean>
    showUpdateExpenseValueToast: (expense: Expense, currencies: string[], updateExpenseValue: (value: number, currency: string) => Promise<Expense>) => Promise<boolean>
    showUpdateExpenseDescriptionToast: (expense: Expense, updateExpenseDescription: (description: string) => Promise<Expense>) => Promise<boolean>
    showRemoveExpenseToast: (removeExpense: () => Promise<void>) => Promise<boolean>
    showCreateNoteToast: (createNote: (content: string) => Promise<Note>) => Promise<boolean>
    showRemoveNoteToast: (removeNote: () => Promise<void>) => Promise<boolean>
    showCreateAirlineToast: (createAirline: (name: string) => Promise<Airline>) => Promise<boolean>
    showUpdateAirlineToast: (airline: Airline, updateAirlineName: (name: string) => Promise<Airline>, updateAirlineLogo: (logo: string) => Promise<Airline>, removeAirlineCode: (code: string) => Promise<void>) => Promise<boolean>
    showRemoveAirlineToast: (airline: Airline, removeAirline: () => Promise<void>) => Promise<boolean>
}