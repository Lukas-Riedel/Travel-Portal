import axios from "axios"
import * as authRefresh from "axios-auth-refresh"
import { getIamResponseWithCredentials, getIamResponseWithRefresh } from "./iamClient.ts"
import type { AxiosInstance, AxiosRequestConfig, AxiosResponse } from "axios"
import { DeviceType, FlightType, PlaceType, RegionType, SpecialPlaceType, TripType, } from "../types/CoreSwaggerTypes.ts"
import type {
    Album, Expense, Flight, Year, Voucher, Document, Device, Label, Airport, Highlight, CategoryCategory, CategoryIncludedEntity, Category, IndexableEntityType,
    GeographicalRegion, CompositeRegion, CategoryMetadata, Fitness, Address, Place as IPlace, PlaceIncludedEntity, PlaceSortingStrategy, PendingPhoto, Photo,
    DataConsistencyIssue, Statistics, Subscription, TimeTrackingEventType, TimeTrackingEvent, TripIncludedEntity, Trip as ITrip, ExpenseType, Note, Airline, YearIncludedEntity,
    Location, SearchResult, TaskPriority, Task,
    ExpenseCurrency
} from "../types/CoreSwaggerTypes.ts"
import { Place } from "../classes/Place.ts"
import { Trip } from "../classes/Trip.ts"
import { useAuthStore } from "../hooks/useAuthStore.ts"
import { GUEST_CREDENTIALS } from "../utils/authenticationUtils.ts"
import type { GeoJSON } from "geojson"

export const refreshPlaceHighlights = async (placeId: string, count: number): Promise<Highlight[]> =>
    coreClient.post<Highlight[]>(createQueryPath(`places/${placeId}/highlights/refresh`,
        {
            count
        })).then(extractData)

export const refreshTripHighlights = async (tripId: string, count: number): Promise<Highlight[]> =>
    coreClient.post<Highlight[]>(createQueryPath(`trips/${tripId}/highlights/refresh`,
        {
            count
        })).then(extractData)

export const refreshCategoryHighlights = async (categoryId: string, count: number): Promise<Highlight[]> =>
    coreClient.post<Highlight[]>(createQueryPath(`categories/${categoryId}/highlights/refresh`,
        {
            count
        })).then(extractData)

export const refreshYearHighlights = async (year: number, count: number): Promise<Highlight[]> =>
    coreClient.post<Highlight[]>(createQueryPath(`years/${year}/highlights/refresh`,
        {
            count
        })).then(extractData)

export const search = async (query: string, { include, limit }: { include?: IndexableEntityType[], limit?: number } = {}, config?: AxiosRequestConfig): Promise<SearchResult[]> =>
    coreClient.get<SearchResult[]>(createQueryPath("search",
        {
            query,
            limit,
            include: include?.join(","),
        }
    ), config).then(extractData)

export const createVoucher = async (code: string, issuer: string, value: number, currency: ExpenseCurrency, expiration?: number): Promise<Voucher> =>
    coreClient.post<Voucher>("vouchers",
        {
            code,
            issuer,
            value,
            currency,
            expiration
        }
    ).then(extractData)

export const listVouchers = async (): Promise<Voucher[]> =>
    coreClient.get<Voucher[]>("vouchers")
        .then(extractData)

export const getVoucher = async (voucherId: string): Promise<Voucher> =>
    coreClient.get<Voucher>(`vouchers/${voucherId}`)
        .then(extractData)

export const updateVoucherValue = async (voucherId: string, value: number): Promise<Voucher> =>
    coreClient.patch<Voucher>(`vouchers/${voucherId}`,
        {
            value
        }
    ).then(extractData)

export const removeVoucher = async (voucherId: string): Promise<void> =>
    coreClient.delete(`vouchers/${voucherId}`)

export const createDocument = async (name: string, code: string, issuer: string, expiration?: number): Promise<Document> =>
    coreClient.post<Document>("documents",
        {
            name,
            code,
            issuer,
            expiration
        }
    ).then(extractData)

export const listDocuments = async (): Promise<Document[]> =>
    coreClient.get<Document[]>("documents")
        .then(extractData)

export const getDocument = async (documentId: string): Promise<Document> =>
    coreClient.get<Document>(`documents/${documentId}`)
        .then(extractData)

export const removeDocument = async (documentId: string): Promise<void> =>
    coreClient.delete(`documents/${documentId}`)

export const createDevice = async (id: string, data: Record<string, any>): Promise<Device> =>
    coreClient.post<Device>("devices",
        {
            id,
            type: DeviceType.Portal,
            name: navigator.userAgent,
            data
        }
    ).then(extractData)

export const listDevices = async ({ type }: { type?: DeviceType } = {}): Promise<Device[]> =>
    coreClient.get<Device[]>(createQueryPath("devices",
        {
            type
        }
    )).then(extractData)

export const getLabel = async (labelId: string): Promise<Label> =>
    coreClient.get<Label>(`labels/${labelId}`)
        .then(extractData)

export const listLabels = async (): Promise<Label[]> =>
    coreClient.get<Label[]>("labels")
        .then(extractData)

export const updateLabelName = async (labelId: string, name: string): Promise<Label> =>
    coreClient.patch<Label>(`labels/${labelId}`,
        {
            name
        }
    ).then(extractData)

export const getAirport = async (airportId: string): Promise<Airport> =>
    coreClient.get<Airport>(`airports/${airportId}`)
        .then(extractData)

export const listAirports = async (): Promise<Airport[]> =>
    coreClient.get<Airport[]>("airports").then(extractData)

export const updateAirportLongName = async (airportId: string, longName: string): Promise<Airport> =>
    coreClient.patch<Airport>(`airports/${airportId}`,
        {
            longName
        }
    ).then(extractData)

export const updateAirportCountry = async (airportId: string, country: string): Promise<Airport> =>
    coreClient.patch<Airport>(`airports/${airportId}`,
        {
            country
        }
    ).then(extractData)

export const getHighlight = async (highlightId: string): Promise<Highlight> =>
    coreClient.get<Highlight>(`highlights/${highlightId}`)
        .then(extractData)

export const updateHighlightQualityAttributes = async (highlightId: string, composition: number | null, sky: number | null, shadows: number | null, circumstances: number | null, atmosphere: number | null, impression: number | null): Promise<Highlight> =>
    coreClient.patch<Highlight>(`highlights/${highlightId}`,
        {
            attributes: {
                composition,
                sky,
                shadows,
                circumstances,
                atmosphere,
                impression
            }
        }
    ).then(extractData)

export const createGeographicalRegion = async (name: string, country: string, category: string, radius: number, geoJson: GeoJSON, overwrite: boolean = false): Promise<GeographicalRegion> =>
    coreClient.post<GeographicalRegion>(createQueryPath("regions",
        {
            type: RegionType.Geographical,
            overwrite
        }
    ),
        {
            category: {
                name,
                category
            },
            countryCategory: {
                name: country
            },
            radius,
            geoJson
        }
    ).then(extractData)

export const createGeographicalExtensionRegion = async (name: string, country: string, category: string, latitude: number, longitude: number): Promise<GeographicalRegion> =>
    coreClient.post<GeographicalRegion>(createQueryPath("regions",
        {
            type: RegionType.GeographicalExtension
        }
    ),
        {
            category: {
                name,
                category
            },
            countryCategory: {
                name: country
            },
            radius: 0,
            geoJson: {
                type: "Feature",
                geometry: {
                    type: "Point",
                    coordinates: [longitude, latitude]
                }
            }
        }
    ).then(extractData)

export const createCompositeRegion = async (name: string, category: string, includedRegions: string[], excludedRegions?: string[], overwrite: boolean = false): Promise<CompositeRegion> =>
    coreClient.post<CompositeRegion>(createQueryPath("regions",
        {
            type: RegionType.Composite,
            overwrite
        }
    ),
        {
            category: {
                name,
                category
            },
            includedCategories: includedRegions.map(includedRegion => ({ name: includedRegion })),
            excludedCategories: excludedRegions?.map(excludedRegion => ({ name: excludedRegion }))
        }
    ).then(extractData)

export const listRegions = async ({ name }: { name?: string } = {}): Promise<(GeographicalRegion | CompositeRegion)[]> =>
    coreClient.get<(GeographicalRegion | CompositeRegion)[]>(createQueryPath("regions",
        {
            name
        }
    )).then(extractData)

export const listCategories = async ({ country, categories, include }: { country?: string, categories?: CategoryCategory[], include?: CategoryIncludedEntity[] } = {}): Promise<Category[]> =>
    coreClient.get<Category[]>(createQueryPath("categories",
        {
            country,
            categories: categories?.join(","),
            include: include?.join(",")
        }
    )).then(extractData)

export const getCategory = async (categoryId: string): Promise<Category> =>
    coreClient.get<Category>(`categories/${categoryId}`)
        .then(extractData)

export const updateCategoryName = async (categoryId: string, name: string): Promise<Category> =>
    coreClient.patch<Category>(`categories/${categoryId}`,
        {
            name
        }
    ).then(extractData)

export const updateCategoryCategory = async (categoryId: string, category: CategoryCategory): Promise<Category> =>
    coreClient.patch<Category>(`categories/${categoryId}`,
        {
            category
        }
    ).then(extractData)

export const updateCategoryMetadata = async (categoryId: string, { unicode, color, publicHolidaysCalendar }: CategoryMetadata = {}): Promise<Category> =>
    coreClient.patch<Category>(`categories/${categoryId}`,
        {
            metadata: {
                unicode,
                color,
                publicHolidaysCalendar
            }
        }
    ).then(extractData)

export const removeCategory = async (categoryId: string): Promise<void> =>
    coreClient.delete(`categories/${categoryId}`)

export const updateCategoryMainHighlight = async (categoryId: string, mainHighlightId: string): Promise<Category> =>
    coreClient.patch<Category>(`categories/${categoryId}`,
        {
            mainHighlight: {
                id: mainHighlightId
            }
        }
    ).then(extractData)

export const createCategoryHighlight = async (categoryId: string, photoId: string): Promise<Highlight> =>
    coreClient.post<Highlight>(`categories/${categoryId}/highlights`,
        {
            photo: {
                id: photoId
            }
        }
    ).then(extractData)

export const removeCategoryHighlight = async (categoryId: string, highlightId: string): Promise<void> =>
    coreClient.delete(`categories/${categoryId}/highlights/${highlightId}`)

export const listConfigurationEntries = async (): Promise<Record<string, any>> =>
    coreClient.get<Record<string, any>>("configuration")
        .then(extractData)

export const replaceConfigurationEntry = async <T>(key: string, value: T): Promise<Record<string, T>> =>
    coreClient.put<Record<string, T>>(`configuration/${key}`, value).then(extractData)

export const replaceFitness = async (timestamp: number, steps: number, seconds: number, distance: number, overwrite: boolean = false): Promise<Fitness> =>
    coreClient.put<Fitness>(createQueryPath(`fitness/${timestamp}`,
        {
            overwrite
        }
    ),
        {
            steps,
            seconds,
            distance
        }
    ).then(extractData)

export const getCoordinates = async (address: string): Promise<Location> =>
    coreClient.get<Location>(createQueryPath("coordinates",
        {
            address
        }
    )).then(extractData)

export const getAddress = async (latitude: number, longitude: number): Promise<Address> =>
    coreClient.get<Address>(createQueryPath("address",
        {
            latitude,
            longitude
        }
    )).then(extractData)

export const createEvent = async (name: string, args?: Record<string, any>): Promise<void> =>
    coreClient.post("events",
        {
            name,
            args
        }
    )

export const createCandidatePlace = async (name: string, address: string): Promise<Place> =>
    coreClient.post<IPlace>(createQueryPath("places",
        {
            type: SpecialPlaceType.Candidate,
            address
        }
    ),
        {
            name
        }
    ).then(extractData)
        .then(place => new Place(place))

export const createPermanentPlace = async (name: string, address: string): Promise<Place> =>
    coreClient.post<IPlace>(createQueryPath("places",
        {
            type: SpecialPlaceType.Permanent,
            address
        }
    ),
        {
            name: name
        }
    ).then(extractData)
        .then(place => new Place(place))

export const listRegularPlaces = async ({ tripId, categoryId, labelId, year, albumId, photoId, minStart, maxEnd, nearbyPlaces, limit, include, sort }:
    { tripId?: string, categoryId?: string, labelId?: string, year?: number, albumId?: string, photoId?: string, minStart?: number, maxEnd?: number, nearbyPlaces?: number, limit?: number, include?: PlaceIncludedEntity[], sort?: PlaceSortingStrategy } = {}): Promise<Place[]> =>
    coreClient.get<IPlace[]>(createQueryPath("places",
        {
            type: PlaceType.Regular,
            tripId,
            categoryId,
            labelId,
            year,
            albumId,
            photoId,
            minStart,
            maxEnd,
            nearbyPlaces,
            limit,
            include: include?.join(","),
            sort
        }
    )).then(extractData)
        .then(places => places.map(place => new Place(place)))

export const listCandidatePlaces = async ({ tripId, categoryId, labelId, nearbyPlaces, limit, include, sort }:
    { tripId?: string, categoryId?: string, labelId?: string, nearbyPlaces?: number, limit?: number, include?: PlaceIncludedEntity[], sort?: PlaceSortingStrategy } = {}): Promise<Place[]> =>
    coreClient.get<IPlace[]>(createQueryPath("places",
        {
            type: PlaceType.Candidate,
            tripId,
            categoryId,
            labelId,
            nearbyPlaces,
            limit,
            include: include?.join(","),
            sort
        }
    )).then(extractData)
        .then(places => places.map(place => new Place(place)))

export const getPlace = async (placeId: string, nearbyPlaces?: number): Promise<Place> =>
    coreClient.get<IPlace>(createQueryPath(`places/${placeId}`,
        {
            nearbyPlaces
        }
    )).then(extractData)
        .then(place => new Place(place))

export const updatePlaceName = async (placeId: string, name: string): Promise<Place> =>
    coreClient.patch<IPlace>(`places/${placeId}`,
        {
            name
        }
    ).then(extractData)
        .then(place => new Place(place))

export const updatePlaceCountry = async (placeId: string, country: string): Promise<Place> =>
    coreClient.patch<IPlace>(`places/${placeId}`,
        {
            country
        }
    ).then(extractData)
        .then(place => new Place(place))

export const updatePlaceLocation = async (placeId: string, latitude: number, longitude: number): Promise<Place> =>
    coreClient.patch<Place>(`places/${placeId}`,
        {
            latitude,
            longitude
        }
    ).then(extractData)
        .then(place => new Place(place))

export const updatePlaceMainHighlight = async (placeId: string, mainHighlightId: string): Promise<Place> =>
    coreClient.patch<IPlace>(`places/${placeId}`,
        {
            mainHighlight: {
                id: mainHighlightId
            }
        }
    ).then(extractData)
        .then(place => new Place(place))

export const updatePlaceExcerpt = async (placeId: string, excerpt: string | null): Promise<Place> =>
    coreClient.patch<IPlace>(`places/${placeId}`,
        {
            excerpt
        }
    ).then(extractData)
        .then(place => new Place(place))

export const removeCandidatePlace = async (placeId: string): Promise<void> =>
    coreClient.delete(`places/${placeId}?type=${SpecialPlaceType.Candidate}`)

export const removePermanentPlace = async (placeId: string): Promise<void> =>
    coreClient.delete(`places/${placeId}?type=${SpecialPlaceType.Permanent}`)

export const createPlaceAlbum = async (placeId: string, timestamp: number): Promise<Album> =>
    coreClient.post<Album>(`places/${placeId}/albums?timestamp=${timestamp}`)
        .then(extractData)

export const refreshPlaceAlbum = async (placeId: string, albumId: string, { mainPhotoPosition, batchId }: { mainPhotoPosition?: number, batchId?: string } = {}): Promise<Album> =>
    coreClient.post<Album>(createQueryPath(`places/${placeId}/albums/${albumId}/refresh`,
        {
            mainPhotoPosition,
            batchId
        }
    )).then(extractData)

export const updatePlaceAlbumsReviewed = async (placeId: string, albumId: string): Promise<Album> =>
    coreClient.patch<Album>(`places/${placeId}/albums/${albumId}`,
        {
            reviewed: true
        }
    ).then(extractData)

export const createPlaceAlbumPhoto = async (placeId: string, albumId: string, fileName: string, data: string, replacedPhotoId: string = undefined): Promise<PendingPhoto> =>
    coreClient.post<PendingPhoto>(`places/${placeId}/albums/${albumId}/photos`,
        {
            fileName,
            data,
            replacedPhotoId
        }
    ).then(extractData)

export const listPlaceAlbumPhotos = async (placeId: string, albumId: string): Promise<Photo[]> =>
    coreClient.get<Photo[]>(`places/${placeId}/albums/${albumId}/photos`)
        .then(extractData)

export const createPlaceHighlight = async (placeId: string, photoId: string): Promise<Highlight> =>
    coreClient.post<Highlight>(`places/${placeId}/highlights`,
        {
            photo: {
                id: photoId
            }
        }
    ).then(extractData)

export const removePlaceHighlight = async (placeId: string, highlightId: string): Promise<void> =>
    coreClient.delete(`places/${placeId}/highlights/${highlightId}`)

export const listDataConsistencyIssues = async (): Promise<DataConsistencyIssue[]> =>
    coreClient.get<DataConsistencyIssue[]>("inconsistencies")
        .then(extractData)

export const listStatistics = async (): Promise<Statistics[]> =>
    coreClient.get<Statistics[]>("statistics")
        .then(extractData)

export const createSubscription = async (description: string, value: number, currency: ExpenseCurrency, expiration: number): Promise<Subscription> =>
    coreClient.post<Subscription>("subscriptions",
        {
            description,
            value,
            currency,
            expiration
        }
    ).then(extractData)

export const listSubscriptions = async (): Promise<Subscription[]> =>
    coreClient.get<Subscription[]>("subscriptions")
        .then(extractData)

export const getSubscription = async (subscriptionId: string): Promise<Subscription> =>
    coreClient.get<Subscription>(`subscriptions/${subscriptionId}`)
        .then(extractData)

export const removeSubscription = async (subscriptionId: string): Promise<void> =>
    coreClient.delete(`subscriptions/${subscriptionId}`)

export const createTimeTrackingEvent = async (type: TimeTrackingEventType, hours: number, description: string, timestamp: number): Promise<TimeTrackingEvent> =>
    coreClient.post<TimeTrackingEvent>("tracker",
        {
            type,
            hours,
            description,
            timestamp
        }
    ).then(extractData)

export const listTimeTrackingEvents = async ({ type }: { type?: TimeTrackingEventType } = {}): Promise<TimeTrackingEvent[]> =>
    coreClient.get<TimeTrackingEvent[]>(createQueryPath("tracker",
        {
            type
        }
    )).then(extractData)

export const removeTimeTrackingEvent = async (eventId: string): Promise<void> =>
    coreClient.delete(`tracker/${eventId}`)

export const listRegularTrips = async ({ year, include }: { year?: number, include?: TripIncludedEntity[] } = {}): Promise<Trip[]> =>
    coreClient.get<ITrip[]>(createQueryPath("trips",
        {
            type: TripType.Regular,
            year,
            include: include?.join(",")
        }
    )).then(extractData)
        .then(trips => trips.map(trip => new Trip(trip)))

export const listCandidateTrips = async ({ include }: { include?: TripIncludedEntity[] } = {}): Promise<Trip[]> =>
    coreClient.get<ITrip[]>(createQueryPath("trips",
        {
            type: TripType.Candidate,
            include: include?.join(",")
        }
    )).then(extractData)
        .then(trips => trips.map(trip => new Trip(trip)))


export const getTrip = async (tripId: string): Promise<Trip> =>
    coreClient.get<Trip>(`trips/${tripId}`)
        .then(extractData)

export const updateTripName = async (tripId: string, name: string): Promise<Trip> =>
    coreClient.patch<ITrip>(`trips/${tripId}`,
        {
            name
        }
    ).then(extractData)
        .then(trip => new Trip(trip))

export const updateTripStart = async (tripId: string, start: number): Promise<Trip> =>
    coreClient.patch<ITrip>(`trips/${tripId}`,
        {
            start
        }
    ).then(extractData)
        .then(trip => new Trip(trip))

export const updateTripMainHighlight = async (tripId: string, mainHighlightId: string): Promise<Trip> =>
    coreClient.patch<ITrip>(`trips/${tripId}`,
        {
            mainHighlight: {
                id: mainHighlightId
            }
        }
    ).then(extractData)
        .then(trip => new Trip(trip))

export const replaceTrip = async (tripId: string, candidateTripId: string): Promise<Trip> =>
    coreClient.put<ITrip>(`trips/${tripId}`,
        {
            id: candidateTripId
        }
    ).then(extractData)
        .then(trip => new Trip(trip))

export const removeTrip = async (tripId: string): Promise<void> =>
    coreClient.delete(`trips/${tripId}`)

export const createTripExpense = async (tripId: string, type: ExpenseType, description: string, value: number, currency: ExpenseCurrency, subscriptionId?: string): Promise<Expense> =>
    coreClient.post<Expense>(`trips/${tripId}/expenses`,
        {
            type,
            description,
            value,
            currency,
            subscription: {
                id: subscriptionId
            }
        }
    ).then(extractData)

export const updateTripExpenseDescription = async (tripId: string, expenseId: string, description: string): Promise<Expense> =>
    coreClient.patch<Expense>(`trips/${tripId}/expenses/${expenseId}`,
        {
            description
        }
    ).then(extractData)

export const updateTripExpenseValue = async (tripId: string, expenseId: string, value: number, currency: ExpenseCurrency): Promise<Expense> =>
    coreClient.patch<Expense>(`trips/${tripId}/expenses/${expenseId}`,
        {
            value,
            currency
        }
    ).then(extractData)

export const removeTripExpense = async (tripId: string, expenseId: string): Promise<void> =>
    coreClient.delete(`trips/${tripId}/expenses/${expenseId}`)

export const logFlight = async (flight: string, from: string, to: string, scheduledDeparture: number,
    scheduledArrival?: number, actualDeparture?: number, actualArrival?: number, fromCode?: string, toCode?: string, aircraft?: string, registration?: string): Promise<Flight> =>
    coreClient.post<Flight>("flights?type=" + FlightType.Logged,
        {
            flight,
            aircraft,
            registration,
            from: {
                name: from,
                code: fromCode
            },
            to: {
                name: to,
                code: toCode
            },
            scheduledDeparture,
            actualDeparture,
            scheduledArrival,
            actualArrival
        }
    ).then(extractData)

export const createScheduledFlight = async (flight: string, from: string, to: string, scheduledDeparture: number, scheduledArrival: number): Promise<Flight> =>
    coreClient.post<Flight>("flights?type=" + FlightType.Scheduled,
        {
            flight,
            from: {
                name: from
            },
            to: {
                name: to
            },
            scheduledDeparture,
            scheduledArrival
        }
    ).then(extractData)

export const createWatchedFlight = async (flight: string, from: string, to: string, scheduledDeparture: number, scheduledArrival: number): Promise<Flight> =>
    coreClient.post<Flight>("flights?type=" + FlightType.Watched,
        {
            flight,
            from: {
                name: from
            },
            to: {
                name: to
            },
            scheduledDeparture,
            scheduledArrival
        }
    ).then(extractData)

export const createTripHighlight = async (tripId: string, photoId: string): Promise<Highlight> =>
    coreClient.post<Highlight>(`trips/${tripId}/highlights`,
        {
            photo: {
                id: photoId
            }
        }
    ).then(extractData)

export const removeTripHighlight = async (tripId: string, highlightId: string): Promise<void> =>
    coreClient.delete(`trips/${tripId}/highlights/${highlightId}`)

export const createPlaceNote = async (placeId: string, content: string): Promise<Note> =>
    coreClient.post<Note>(`places/${placeId}/notes`,
        {
            content
        }
    ).then(extractData)

export const updatePlaceNoteContent = async (placeId: string, noteId: string, content: string): Promise<Note> =>
    coreClient.patch<Note>(`places/${placeId}/notes/${noteId}`,
        {
            content
        }
    ).then(extractData)

export const removePlaceNote = async (placeId: string, noteId: string): Promise<void> =>
    coreClient.delete(`places/${placeId}/notes/${noteId}`)

export const createTripTask = async (tripId: string, description: string, priority: TaskPriority, deadline?: number): Promise<Task> =>
    coreClient.post<Task>(`trips/${tripId}/tasks`,
        {
            description,
            priority,
            deadline
        }
    ).then(extractData)

export const updateTripTaskDescription = async (tripId: string, taskId: string, description: string): Promise<Task> =>
    coreClient.patch<Task>(`trips/${tripId}/tasks/${taskId}`,
        {
            description
        }
    ).then(extractData)

export const updateTripTaskPriority = async (tripId: string, taskId: string, priority: TaskPriority): Promise<Task> =>
    coreClient.patch<Task>(`trips/${tripId}/tasks/${taskId}`,
        {
            priority
        }
    ).then(extractData)

export const removeTripTask = async (tripId: string, taskId: string): Promise<void> =>
    coreClient.delete(`trips/${tripId}/tasks/${taskId}`)

export const createTripNote = async (tripId: string, content: string): Promise<Note> =>
    coreClient.post<Note>(`trips/${tripId}/notes`,
        {
            content
        }
    ).then(extractData)

export const updateTripNoteContent = async (tripId: string, noteId: string, content: string): Promise<Note> =>
    coreClient.patch<Note>(`trips/${tripId}/notes/${noteId}`,
        {
            content
        }
    ).then(extractData)

export const removeTripNote = async (tripId: string, noteId: string): Promise<void> =>
    coreClient.delete(`trips/${tripId}/notes/${noteId}`)

export const createPlaceLabel = async (placeId: string, name: string): Promise<Label> =>
    coreClient.post<Label>(`places/${placeId}/labels`,
        {
            name
        }
    ).then(extractData)

export const removePlaceLabel = async (placeId: string, labelId: string): Promise<void> =>
    coreClient.delete(`places/${placeId}/labels/${labelId}`)

export const createAirlineCode = async (airlineId: string, code: string): Promise<Airline> =>
    coreClient.post<Airline>(`airlines/${airlineId}/codes`,
        {
            code
        }
    ).then(extractData)

export const removeAirlineCode = async (airlineId: string, code: string): Promise<void> =>
    coreClient.delete(`airlines/${airlineId}/codes/${code}`)

export const createAirline = async (name: string, { logo }: { logo?: string } = {}): Promise<Airline> =>
    coreClient.post<Airline>("airlines",
        {
            name,
            logo
        }
    ).then(extractData)

export const listAirlines = async (): Promise<Airline[]> =>
    coreClient.get<Airline[]>("airlines")
        .then(extractData)

export const getAirline = async (airlineId: string): Promise<Airline> =>
    coreClient.get<Airline>(`airlines/${airlineId}`)
        .then(extractData)

export const removeAirline = async (airlineId: string): Promise<void> =>
    coreClient.delete(`airlines/${airlineId}`)

export const updateAirlineName = async (airlineId: string, name: string): Promise<Airline> =>
    coreClient.patch<Airline>(`airlines/${airlineId}`,
        {
            name
        }
    ).then(extractData)

export const updateAirlineLogo = async (airlineId: string, logo: string): Promise<Airline> =>
    coreClient.patch<Airline>(`airlines/${airlineId}`,
        {
            logo
        }
    ).then(extractData)

export const listYears = async ({ include }: { include?: YearIncludedEntity[] } = {}): Promise<Year[]> =>
    coreClient.get<Year[]>(createQueryPath("years",
        {
            include: include?.join(",")
        }
    )).then(extractData)

export const getYear = async (year: number): Promise<Year> =>
    coreClient.get<Year>(`years/${year}`)
        .then(extractData)

export const updateYearMainHighlight = async (year: number, mainHighlightId: string): Promise<Year> =>
    coreClient.patch<Year>(`years/${year}`,
        {
            mainHighlight: {
                id: mainHighlightId
            }
        }
    ).then(extractData)

export const createYearHighlight = async (year: number, photoId: string): Promise<Highlight> =>
    coreClient.post<Highlight>(`years/${year}/highlights`,
        {
            photo: {
                id: photoId
            }
        }
    ).then(extractData)

export const removeYearHighlight = async (year: number, highlightId: string): Promise<void> =>
    coreClient.delete(`years/${year}/highlights/${highlightId}`)

export const refreshAccessToken = async (): Promise<string> => {
    const { refreshToken, setIamResponse } = useAuthStore.getState()
    const { username: fallbackUsername, password: fallbackPassword } = GUEST_CREDENTIALS

    if (!refreshToken) {
        const newIamResponse = await getIamResponseWithCredentials(fallbackUsername, fallbackPassword)
        setIamResponse(newIamResponse)
        return Promise.resolve(newIamResponse.accessToken)
    }

    try {
        const newIamResponse = await getIamResponseWithRefresh(refreshToken)
        setIamResponse(newIamResponse)
        return Promise.resolve(newIamResponse.accessToken)
    }
    catch (error) {
        const newIamResponse = await getIamResponseWithCredentials(fallbackUsername, fallbackPassword)
        setIamResponse(newIamResponse)
        return Promise.resolve(newIamResponse.accessToken)
    }
}

const coreClient: AxiosInstance = axios.create({
    baseURL: window.env?.VITE_CORE_BASE_URL || import.meta.env.VITE_CORE_BASE_URL,
    headers: {
        "Content-Type": "application/json",
        "Request-Origin": window.env?.VITE_APP_NAME || import.meta.env.VITE_APP_NAME
    }
})

const doRefreshAccessToken = async (error: any): Promise<AxiosRequestConfig> => {
    const failedRequestConfig = error.response?.config

    if (!failedRequestConfig) {
        return Promise.reject(error)
    }

    failedRequestConfig.headers = failedRequestConfig.headers || {}
    failedRequestConfig.headers["Authorization"] = `Bearer ${await refreshAccessToken()}`

    return failedRequestConfig
}

const createAuthRefreshInterceptor = (authRefresh as any).default ?? (authRefresh as any)
createAuthRefreshInterceptor(coreClient, doRefreshAccessToken)

coreClient.interceptors.request.use(config => {
    const accessToken = useAuthStore.getState().accessToken
    if (accessToken) {
        config.headers.Authorization = `Bearer ${accessToken}`
    }

    return config
})

const createQueryPath = (path: string, params: Record<string, string | number | boolean | undefined>): string => {
    const entries = Object.entries(params).filter(([_, v]) => v !== undefined).map(([k, v]) => [k, String(v)])
    const queryString = entries.length ? `?${new URLSearchParams(entries)}` : ""

    return path + queryString
}

const extractData = <T>(response: AxiosResponse<T>): T => response.data