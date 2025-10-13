import axios from "axios"
import createAuthRefreshInterceptor from "axios-auth-refresh"
import { getAccessToken, getRefreshToken, logout, setIamResponse } from "../hooks/useAuthStore"
import { getIamResponseWithRefresh } from "./iamClient"
import Place from "../model/place"

export const createDevice = async (deviceId, data) =>
    coreClient.post("devices",
        {
            id: deviceId,
            type: "portal",
            name: navigator.userAgent,
            data
        }
    ).then(extractData)

export const listDevices = async ({ type } = {}) =>
    coreClient.get(createQueryPath("devices",
        {
            type
        }
    )).then(extractData)

export const getLabel = async (labelId) =>
    coreClient.get(`labels/${labelId}`).then(extractData)

export const listLabels = async () =>
    coreClient.get("labels").then(extractData)

export const updateLabelName = async (labelId, name) =>
    coreClient.patch(`labels/${labelId}`,
        {
            name
        }
    ).then(extractData)

export const getAirport = async (airportId) =>
    coreClient.get(`airports/${airportId}`).then(extractData)

export const listAirports = async () =>
    coreClient.get("airports").then(extractData)

export const updateAirportLongName = async (airportId, longName) =>
    coreClient.patch(`airports/${airportId}`,
        {
            longName
        }
    ).then(extractData)

export const getHighlight = async (highlightId) =>
    coreClient.get(`highlights/${highlightId}`).then(extractData)

export const updateHighlightQualityAttributes = async (highlightId, composition, sky, shadows, circumstances, atmosphere) =>
    coreClient.patch(`highlights/${highlightId}`,
        {
            attributes: {
                composition,
                sky,
                shadows,
                circumstances,
                atmosphere
            }
        }
    ).then(extractData)

export const createGeographicalRegion = async (name, country, category, radius, geoJson, overwrite = false) =>
    coreClient.post(createQueryPath("regions",
        {
            type: "geographical",
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

export const createGeographicalExtensionRegion = async (name, country, category, latitude, longitude) =>
    coreClient.post("regions?type=geographicalExtension",
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

export const createCompositeRegion = async (name, category, includedRegions, excludedRegions, overwrite = false) =>
    coreClient.post(createQueryPath("regions",
        {
            type: "composite",
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

export const listRegions = async ({ name } = {}) =>
    coreClient.get(createQueryPath("regions",
        {
            name
        }
    )).then(extractData)

export const listCategories = async ({ country, categories, include } = {}) =>
    coreClient.get(createQueryPath("categories",
        {
            country,
            categories,
            include
        }
    )).then(extractData)

export const getCategory = async (categoryId) =>
    coreClient.get(`categories/${categoryId}`).then(extractData)

export const updateCategoryName = async (categoryId, name) =>
    coreClient.patch(`categories/${categoryId}`,
        {
            name
        }
    ).then(extractData)

export const updateCategoryCategory = async (categoryId, category) =>
    coreClient.patch(`categories/${categoryId}`,
        {
            category
        }
    ).then(extractData)

export const updateCategoryMetadata = async (categoryId, { unicode, color, publicHolidaysCalendar } = {}) =>
    coreClient.patch(`categories/${categoryId}`,
        {
            metadata: {
                unicode,
                color,
                publicHolidaysCalendar
            }
        }
    ).then(extractData)

export const removeCategory = async (categoryId) =>
    coreClient.delete(`categories/${categoryId}`)

export const updateCategoryMainHighlight = async (categoryId, mainHighlightId) =>
    coreClient.patch(`categories/${categoryId}`,
        {
            mainHighlight: {
                id: mainHighlightId
            }
        }
    ).then(extractData)

export const createCategoryHighlight = async (categoryId, photoId) =>
    coreClient.post(`categories/${categoryId}/highlights`,
        {
            photo: {
                id: photoId
            }
        }
    ).then(extractData)

export const removeCategoryHighlight = async (categoryId, highlightId) =>
    coreClient.delete(`categories/${categoryId}/highlights/${highlightId}`)

export const listConfigurationEntries = async () =>
    coreClient.get("configuration").then(extractData)

export const replaceConfigurationEntry = async (key, value) =>
    coreClient.put(`configuration/${key}`, value).then(extractData)

export const replaceFitness = async (timestamp, steps, seconds, distance, overwrite = false) =>
    coreClient.put(createQueryPath(`fitness/${timestamp}`,
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

export const getCoordinates = async (address) =>
    coreClient.get(createQueryPath("coordinates",
        {
            address
        }
    )).then(extractData)

export const getAddress = async (latitude, longitude) =>
    coreClient.get(createQueryPath("address",
        {
            latitude,
            longitude
        }
    )).then(extractData)

export const createEvent = async (name, args) =>
    coreClient.post("events",
        {
            name,
            args
        }
    )

export const createCandidatePlace = async (name, address) =>
    coreClient.post(createQueryPath("places",
        {
            type: "candidate",
            address
        }
    ),
        {
            name: name
        }
    ).then(extractData).then(place => new Place(place))

export const createPermanentPlace = async (name, address) =>
    coreClient.post(createQueryPath("places",
        {
            type: "permanent",
            address
        }
    ),
        {
            name: name
        }
    ).then(extractData).then(place => new Place(place))

export const listRegularPlaces = async ({ tripId, categoryId, labelId, year, albumId, photoId, minStart, maxEnd, limit, include, sort } = {}) =>
    coreClient.get(createQueryPath("places",
        {
            type: "regular",
            tripId,
            categoryId,
            labelId,
            year,
            albumId,
            photoId,
            minStart,
            maxEnd,
            limit,
            include,
            sort
        }
    )).then(extractData).then(places => places.map(place => new Place(place)))

export const listCandidatePlaces = async ({ tripId, categoryId, labelId, limit, include, sort } = {}) =>
    coreClient.get(createQueryPath("places",
        {
            type: "candidate",
            tripId,
            categoryId,
            labelId,
            limit,
            include,
            sort
        }
    )).then(extractData)

export const getPlace = async (placeId) =>
    coreClient.get(`places/${placeId}`).then(extractData).then(place => new Place(place))

export const updatePlaceName = async (placeId, name) =>
    coreClient.patch(`places/${placeId}`,
        {
            name
        }
    ).then(extractData).then(place => new Place(place))

export const updatePlaceLocation = async (placeId, latitude, longitude) =>
    coreClient.patch(`places/${placeId}`,
        {
            latitude,
            longitude
        }
    ).then(extractData).then(place => new Place(place))

export const updatePlaceMainHighlight = async (placeId, mainHighlightId) =>
    coreClient.patch(`places/${placeId}`,
        {
            mainHighlight: {
                id: mainHighlightId
            }
        }
    ).then(extractData).then(place => new Place(place))

export const updatePlaceExcerpt = async (placeId, excerpt) =>
    coreClient.patch(`places/${placeId}`,
        {
            excerpt
        }
    ).then(extractData).then(place => new Place(place))

export const removeCandidatePlace = async (placeId) =>
    coreClient.delete(`places/${placeId}?type=candidate`)

export const removePermanentPlace = async (placeId) =>
    coreClient.delete(`places/${placeId}?type=permanent`)

export const createPlaceAlbum = async (placeId, timestamp) =>
    coreClient.post(`places/${placeId}/albums?timestamp=${timestamp}`).then(extractData)

export const refreshPlaceAlbum = async (placeId, albumId, { mainPhotoPosition } = {}) =>
    coreClient.post(createQueryPath(`places/${placeId}/albums/${albumId}/refresh`,
        {
            mainPhotoPosition
        }
    )).then(extractData)

export const updatePlaceAlbumReviewed = async (placeId, albumId) =>
    coreClient.patch(`places/${placeId}/albums/${albumId}`,
        {
            reviewed: true
        }
    ).then(extractData)

export const createPlaceAlbumPhoto = async (placeId, albumId, fileName, data) =>
    coreClient.post(`places/${placeId}/albums/${albumId}/photos`,
        {
            fileName,
            data
        }
    ).then(extractData)

export const listPlaceAlbumPhotos = async (placeId, albumId) =>
    coreClient.get(`places/${placeId}/albums/${albumId}/photos`).then(extractData)

export const createPlaceHighlight = async (placeId, photoId) =>
    coreClient.post(`places/${placeId}/highlights`,
        {
            photo: {
                id: photoId
            }
        }
    ).then(extractData)

export const removePlaceHighlight = async (placeId, highlightId) =>
    coreClient.delete(`places/${placeId}/highlights/${highlightId}`)

export const listDataConsistencyIssues = async () =>
    coreClient.get("inconsistencies").then(extractData)

export const listStatistics = async () =>
    coreClient.get("statistics").then(extractData)

export const createSubscription = async (description, value, currency, expiration) =>
    coreClient.post("subscriptions",
        {
            description,
            value,
            currency,
            expiration
        }
    ).then(extractData)

export const listSubscriptions = async () =>
    coreClient.get("subscriptions").then(extractData)

export const getSubscription = async (subscriptionId) =>
    coreClient.get(`subscriptions/${subscriptionId}`).then(extractData)

export const removeSubscription = async (subscriptionId) =>
    coreClient.delete(`subscriptions/${subscriptionId}`)

export const createTimeTrackingEvent = async (type, hours, description, timestamp) =>
    coreClient.post("tracker",
        {
            type,
            hours,
            description,
            timestamp
        }
    ).then(extractData)

export const listTimeTrackingEvents = async ({ type } = {}) =>
    coreClient.get(createQueryPath("tracker",
        {
            type
        }
    )).then(extractData)

export const removeTimeTrackingEvent = async (eventId) =>
    coreClient.delete(`tracker/${eventId}`)

export const listRegularTrips = async ({ year, include } = {}) =>
    coreClient.get(createQueryPath("trips",
        {
            type: "regular",
            year,
            include
        }
    )).then(extractData)

export const listCandidateTrips = async ({ include } = {}) =>
    coreClient.get(createQueryPath("trips",
        {
            type: "candidate",
            include
        }
    )).then(extractData)

export const getTrip = async (tripId) =>
    coreClient.get(`trips/${tripId}`).then(extractData)

export const updateTripName = async (tripId, name) =>
    coreClient.patch(`trips/${tripId}`,
        {
            name
        }
    ).then(extractData)

export const updateTripStart = async (tripId, start) =>
    coreClient.patch(`trips/${tripId}`,
        {
            start
        }
    ).then(extractData)

export const updateTripMainHighlight = async (tripId, mainHighlightId) =>
    coreClient.patch(`trips/${tripId}`,
        {
            mainHighlight: {
                id: mainHighlightId
            }
        }
    ).then(extractData)

export const replaceTrip = async (tripId, candidateTripId) =>
    coreClient.put(`trips/${tripId}`,
        {
            id: candidateTripId
        }
    ).then(extractData)

export const removeTrip = async (tripId) =>
    coreClient.delete(`trips/${tripId}`)

export const createTripExpense = async (tripId, type, description, value, currency, subscriptionId) =>
    coreClient.post(`trips/${tripId}/expenses`,
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

export const updateTripExpenseDescription = async (tripId, expenseId, description) =>
    coreClient.patch(`trips/${tripId}/expenses/${expenseId}`,
        {
            description
        }
    ).then(extractData)

export const updateTripExpenseValue = async (tripId, expenseId, value, currency) =>
    coreClient.patch(`trips/${tripId}/expenses/${expenseId}`,
        {
            value,
            currency
        }
    ).then(extractData)

export const removeTripExpense = async (tripId, expenseId) =>
    coreClient.delete(`trips/${tripId}/expenses/${expenseId}`)

export const logFlight = async (flight, from, to, scheduledDeparture) =>
    coreClient.post("flights?type=logged",
        {
            flight,
            from: {
                name: from
            },
            to: {
                name: to
            },
            scheduledDeparture
        }
    ).then(extractData)

export const logFlightManually = async (flight, aircraft, registration, from, fromCode, to, toCode,
    scheduledDeparture, actualDeparture, scheduledArrival, actualArrival) =>
    coreClient.post("flights?type=logged",
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

export const createScheduledFlight = async (flight, from, to, scheduledDeparture, scheduledArrival) =>
    coreClient.post("flights?type=scheduled",
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

export const createWatchedFlight = async (flight, from, to, scheduledDeparture, scheduledArrival) =>
    coreClient.post("flights?type=watched",
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

export const createTripHighlight = async (tripId, photoId) =>
    coreClient.post(`trips/${tripId}/highlights`,
        {
            photo: {
                id: photoId
            }
        }
    ).then(extractData)

export const removeTripHighlight = async (tripId, highlightId) =>
    coreClient.delete(`trips/${tripId}/highlights/${highlightId}`)

export const createTripNote = async (tripId, content) =>
    coreClient.post(`trips/${tripId}/notes`,
        {
            content
        }
    ).then(extractData)

export const createPlaceNote = async (placeId, content) =>
    coreClient.post(`places/${placeId}/notes`,
        {
            content
        }
    ).then(extractData)

export const removeTripNote = async (tripId, noteId) =>
    coreClient.delete(`trips/${tripId}/notes/${noteId}`)

export const removePlaceNote = async (placeId, noteId) =>
    coreClient.delete(`places/${placeId}/notes/${noteId}`)

export const createPlaceLabel = async (placeId, name) =>
    coreClient.post(`places/${placeId}/labels`,
        {
            name
        }
    ).then(extractData)

export const removePlaceLabel = async (placeId, labelId) =>
    coreClient.delete(`places/${placeId}/labels/${labelId}`)

export const createAirlineCode = async (airlineId, code) =>
    coreClient.post(`airlines/${airlineId}/codes`,
        {
            code
        }
    ).then(extractData)

export const removeAirlineCode = async (airlineId, code) =>
    coreClient.delete(`airlines/${airlineId}/codes/${code}`)

export const createAirline = async (name, { logo } = {}) =>
    coreClient.post("airlines",
        {
            name,
            logo
        }
    ).then(extractData)

export const listAirlines = async () =>
    coreClient.get("airlines").then(extractData)

export const getAirline = async (airlineId) =>
    coreClient.get(`airlines/${airlineId}`).then(extractData)

export const removeAirline = async (airlineId) =>
    coreClient.delete(`airlines/${airlineId}`)

export const updateAirlineName = async (airlineId, name) =>
    coreClient.patch(`airlines/${airlineId}`,
        {
            name
        }
    ).then(extractData)

export const updateAirlineLogo = async (airlineId, logo) =>
    coreClient.patch(`airlines/${airlineId}`,
        {
            logo
        }
    ).then(extractData)

export const listYears = async ({ include } = {}) =>
    coreClient.get(createQueryPath("years",
        {
            include
        }
    )).then(extractData)

export const getYear = async (year) =>
    coreClient.get(`years/${year}`).then(extractData)

export const updateYearMainHighlight = async (year, mainHighlightId) =>
    coreClient.patch(`years/${year}`,
        {
            mainHighlight: {
                id: mainHighlightId
            }
        }
    ).then(extractData)

export const createYearHighlight = async (year, photoId) =>
    coreClient.post(`years/${year}/highlights`,
        {
            photo: {
                id: photoId
            }
        }
    ).then(extractData)

export const removeYearHighlight = async (year, highlightId) =>
    coreClient.delete(`years/${year}/highlights/${highlightId}`)

export const refreshAccessToken = async () => {
    const refreshToken = getRefreshToken()

    if (!refreshToken) {
        logout()
        return Promise.reject()
    }

    try {
        const newIamResponse = setIamResponse(await getIamResponseWithRefresh(refreshToken))
        return Promise.resolve(newIamResponse.data.accessToken)
    }
    catch (error) {
        logout()
        return Promise.reject(error)
    }
}

const coreClient = axios.create({
    baseURL: import.meta.env.VITE_CORE_BASE_URL,
    headers: {
        "Content-Type": "application/json",
    }
})

const doRefreshAccessToken = async (failedRequest) => {
    failedRequest.response.config.headers["Authorization"] = `Bearer ${await refreshAccessToken()}`
}

createAuthRefreshInterceptor(coreClient, doRefreshAccessToken)

coreClient.interceptors.request.use(config => {
    const accessToken = getAccessToken()
    if (accessToken) {
        config.headers.Authorization = `Bearer ${accessToken}`
    }

    return config
})

const createQueryPath = (path, params) => {
    const entries = Object.entries(params).filter(([_, v]) => v !== undefined)
    const queryString = entries.length ? `?${new URLSearchParams(entries)}` : ""

    return path + queryString
}

const extractData = response => response.data