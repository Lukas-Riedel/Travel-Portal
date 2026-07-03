import type { Place } from "../classes/Place.ts"
import type { Trip } from "../classes/Trip.ts"
import type { Airline, Airport, Album, Category, CategoryCategory, CategoryMetadata, CompositeRegion, Device, Document, Expense, Fitness, Flight, FlightType, GeographicalRegion, Highlight, HighlightAttributes, Label, Note, Subscription, Task, TaskPriority, TimeBasedFitness, TimeTrackingEvent, TimeTrackingEventType, Voucher } from "./CoreSwaggerTypes.ts"
import type { Highlightable } from "./Highlightable.ts"

export interface UsePredefinedUserInputResult {
    showSelectHighlightsToast: (selectHighlights: (count: number) => Promise<void>) => Promise<boolean>
    showAssignCategoryToast: (categories: Category[], assignCategory: (categoryName: string) => Promise<GeographicalRegion>) => Promise<boolean>
    showAssignAirlineCodeToast: (airlines: Airline[], assignAirlineCode: (airlineId: string) => Promise<Airline>) => Promise<boolean>
    showUpdateCategoryToast: (category: Category, updateMetadata: (metadata: CategoryMetadata) => Promise<Category>, updateCategory?: (category: CategoryCategory) => Promise<Category>) => Promise<boolean>
    showReplaceFitnessToast: (fitnessRecords: Fitness[], replaceFitness: (fitnessRecordIndex: number) => Promise<Fitness>) => Promise<boolean>
    showReplacePhotoToast: (agent: Device[], replacePhoto: (path: string, agentId: string, sendNotification: boolean) => Promise<void>) => Promise<boolean>
    showCreateMultipleGeographicalRegionsToast: (createGeographicalRegions: (geoJson: object) => Promise<void>) => Promise<boolean>
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
    showLoginToast: (login: (username: string, password: string) => Promise<void>) => Promise<boolean>
    showLogoutToast: (logout: () => Promise<void>) => Promise<boolean>
    showUpdatePlaceLocationToast: (updatePlaceLocation: () => Promise<Place>) => Promise<boolean>
    showUpdatePlaceReviewedToast: (updatePlaceReviewed: () => Promise<Album[]>) => Promise<boolean>
    showRefreshPlaceExcerptToast: (refreshPlaceExcerpt: () => Promise<Place>) => Promise<boolean>
    showUpdateNoteToast: (updateNote: () => Promise<Note>) => Promise<boolean>
    showRemoveDocumentToast: (removeDocument: () => Promise<void>) => Promise<boolean>
    showUpdateConfigurationEntryToast: (updateConfigurationEntry: () => Promise<Record<string, any>>) => Promise<boolean>
    showRemovePlaceToast: (placesOrRemovePlace: Place[] | (() => Promise<void>), removePlace?: (placeId: string) => Promise<void>) => Promise<boolean>
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
    showLogFlightToast: (logFlight: (actualDeparture?: Date, actualArrival?: Date, fromCode?: string, toCode?: string, aircraft?: string, registration?: string) => Promise<Flight>) => Promise<boolean>
    showCreateHighlightToast: (createHighlight: () => Promise<Highlight>) => Promise<boolean>
    showUpdateMainHighlightToast: <T extends Highlightable> (updateMainHighlight: () => Promise<T>) => Promise<boolean>
    showUpdateHighlightToast: (updateHighlight: () => Promise<Highlight>) => Promise<boolean>
    showRemoveHighlightToast: (removeHighlight: () => Promise<void>) => Promise<boolean>
    showAssignLabelToast: (createLabel: () => Promise<Label>) => Promise<boolean>
    showUnassignLabelToast: (removeLabel: () => Promise<void>) => Promise<boolean>
    showUploadPhotosToast: (agents: Device[], uploadPhotos: ((path: string, agentId: string, sendNotification: boolean, mainPhotoPosition?: number) => Promise<void>) | ((date: string, path: string, agentId: string, sendNotification: boolean, mainPhotoPosition?: number) => Promise<void>), sendNotification?: boolean, defaultPath?: string) => Promise<boolean>
    showUpdateHighlightAttributesToast: (updateHighlightAttributes: (composition: number | null, sky: number | null, shadows: number | null, circumstances: number | null, atmosphere: number | null) => Promise<Highlight>, highlightAttributes?: HighlightAttributes, timestamp?: number, timezone?: string, sunAltitude?: number) => Promise<boolean>
    showOverwriteGeographicalRegionToast: (region: GeographicalRegion, overwriteGeographicalRegion: (radius: number, geoJson: object) => Promise<GeographicalRegion>) => Promise<boolean>
    showOverwriteCompositeRegionToast: (region: CompositeRegion, overwriteCompositeRegion: (includedCategoryNames: string[], excludedCategoryNames: string[]) => Promise<CompositeRegion>) => Promise<boolean>
    showSubtractVoucherValueToast: (subtractVoucherValue: (value: number) => Promise<Voucher>) => Promise<boolean>
    showCreatePlaceToast: (createPlace: (name: string, address: string) => Promise<Place>) => Promise<boolean>
    showMoveTripToast: (moveTrip: (date: Date) => Promise<Trip>) => Promise<boolean>
    showLoadTripToast: (tripCandidates: Trip[], loadTrip: (tripId: string) => Promise<Trip>) => Promise<boolean>
    showCreateOvertimeToast: (defaultOvertimeHours: number, createOvertime: (description: string, hours: number) => Promise<TimeTrackingEvent>) => Promise<boolean>
    showCreatePlannedWorkToast: (defaultPlannedWorkHours: number, createPlannedWork: (hours: number) => Promise<TimeTrackingEvent>) => Promise<boolean>
    showCreateNegativeTimeTrackingEventToast: (type: TimeTrackingEventType, defaultHours: number, createNegativeTimeTrackingEvent: (hours: number) => Promise<TimeTrackingEvent>) => Promise<boolean>
    showCreateFlightToast: (createFlight: (flight: string, from: string, scheduledDeparture: Date, to: string, scheduledArrival: Date, type: FlightType) => Promise<Flight>) => Promise<boolean>
    showCreateSubscriptionToast: (currencies: string[], createSubscription: (description: string, value: number, currency: string, expiration: Date) => Promise<Subscription>) => Promise<boolean>
    showCreateDocumentToast: (createDocument: (name: string, identifier: string, issuer: string, expiration?: Date) => Promise<Document>) => Promise<boolean>
    showCreateVoucherToast: (currencies: string[], createVoucher: (identifier: string, issuer: string, value: number, currency: string, expiration?: Date) => Promise<Voucher>) => Promise<boolean>
    showSynchronizePhotosToast: (synchronizePhotos: (path: string, expiration: Date) => Promise<void>) => Promise<boolean>
    showCreateGeographicalRegionToast: (countryCategories: Category[], createGeographicalRegion: (name: string, category: CategoryCategory, geoJson: object, country?: string, radius?: number) => Promise<GeographicalRegion>, templateRegion?: GeographicalRegion) => Promise<boolean>
    showCreateCompositeRegionToast: (createCompositeRegion: (name: string, category: CategoryCategory, includedCategoryNames: string[], excludedCategoryNames: string[]) => Promise<CompositeRegion>) => Promise<boolean>
    showCreateSelectedRegionToast: (countryCategories: Category[], createGeoJsonRegion: (geoJson: object) => object, extractGeoJsonFeatures: (geoJson: object) => any[], createGeographicalRegion: (name: string, category: CategoryCategory, geoJson: object, country?: string, radius?: number) => Promise<GeographicalRegion>, createCompositeRegion: (name: string, category: CategoryCategory, includedCategoryNames: string[], excludedCategoryNames: string[]) => Promise<CompositeRegion>) => Promise<boolean>
    showCopyTripItineraryToast: (copyTripItinerary: () => Promise<void>) => Promise<boolean>
    showCreateTripTaskToast: (trips: Trip[], createTripTask: (tripId: string, description: string, priority: TaskPriority, deadline?: Date) => Promise<Task>) => Promise<boolean>
    showRemoveTaskToast: (removeTask: () => Promise<void>) => Promise<boolean>
    showUpdateTaskPriorityToast: (updateTaskPriority: (priority: TaskPriority) => Promise<Task>) => Promise<boolean>
    showUpdateTaskDescriptionToast: (description: string, updateTaskDescription: (description: string) => Promise<Task>) => Promise<boolean>
}