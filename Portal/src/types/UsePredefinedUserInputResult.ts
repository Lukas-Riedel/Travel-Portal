import type { Place } from "../classes/Place.ts"
import type { Airline, Airport, Album, Document, Expense, Flight, Highlight, Label, Note, Subscription, Voucher } from "./CoreSwaggerTypes.ts"
import type { Highlightable } from "./Highlightable.ts"

export interface UsePredefinedUserInputResult {
    showCreateMultipleGeographicalRegionsToast: (createGeographicalRegions: (geoJson: string) => Promise<void>) => Promise<boolean>
    showRemoveVoucherToast: (removeVoucher: () => Promise<void>) => Promise<boolean>
    showRemoveTripToast: (removeTrip: () => Promise<void>) => Promise<boolean>
    showCopyTimeTrackingEventDescriptionToast: (copyTimeTrackingEventDescription: () => Promise<void>) => Promise<boolean>
    showRemoveTimeTrackingEventToast: (removeTimeTrackingEvent: () => Promise<void>) => Promise<boolean>
    showRemoveSubscriptionToast: (removeSubscription: () => Promise<void>) => Promise<boolean>
    showCopyRegionGeoJsonToast: (copyRegionGeoJson: () => Promise<void>) => Promise<boolean>
    showUpdatePlaceAddressToast: (place: Place, updatePlaceAddress: (address: string) => Promise<Place>) => Promise<boolean>
    showUpdatePlaceExcerptToast: (place: Place, updatePlaceExcerpt: (excerpt: string) => Promise<Place>) => Promise<boolean>
    showUpdateEntityNameToast: <T> (name: string, updateEntityName: (name: string) => Promise<T>) => Promise<boolean>
    showRemoveEntityToast: (removeEntity: () => Promise<void>) => Promise<boolean>
    showCreateLabelToast: (createLabel: (name: string) => Promise<Label>) => Promise<boolean>
    showUpdateAirlineLogoToast: (updateAirlineLogo: (logo: string) => Promise<Airline>) => Promise<boolean>
    showUpdateAirportCountryToast: (updateAirportCountry: (country: string) => Promise<Airport>) => Promise<boolean>
    showUpdateAirportNameToast: (updateAirportName: (name: string) => Promise<Airport>) => Promise<boolean>
    showUpdatePlaceCountryToast: (updatePlaceCountry: (country: string) => Promise<Place>) => Promise<boolean>
    showLogoutToast: (logout: () => Promise<void>) => Promise<boolean>
    showUpdatePlaceLocationToast: (updatePlaceLocation: () => Promise<Place>) => Promise<boolean>
    showUpdatePlaceReviewedToast: (updatePlaceReviewed: () => Promise<Album[]>) => Promise<boolean>
    showRefreshPlaceExcerptToast: (refreshPlaceExcerpt: () => Promise<Place>) => Promise<boolean>
    showUpdateNoteToast: (updateNote: () => Promise<Note>) => Promise<boolean>
    showRemoveDocumentToast: (removeDocument: () => Promise<void>) => Promise<boolean>
    showUpdateConfigurationEntryToast: (updateConfigurationEntry: () => Promise<Record<string, any>>) => Promise<boolean>
    showRemovePlaceToast: (removePlace: () => Promise<void>) => Promise<boolean>
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
    showRemoveAirlineToast: (removeAirline: () => Promise<void>) => Promise<boolean>
    showRemovePhotoToast: (removePhoto: () => Promise<void>) => Promise<boolean>
    showLogFlightToast: (logFlight: () => Promise<Flight>) => Promise<boolean>
    showCreateHighlightToast: (createHighlight: () => Promise<Highlight>) => Promise<boolean>
    showUpdateMainHighlightToast: <T extends Highlightable> (updateMainHighlight: () => Promise<T>) => Promise<boolean>
    showUpdateHighlightToast: (updateHighlight: () => Promise<Highlight>) => Promise<boolean>
    showRemoveHighlightToast: (removeHighlight: () => Promise<void>) => Promise<boolean>
    showAssignLabelToast: (createLabel: () => Promise<Label>) => Promise<boolean>
    showUnassignLabelToast: (removeLabel: () => Promise<void>) => Promise<boolean>
}