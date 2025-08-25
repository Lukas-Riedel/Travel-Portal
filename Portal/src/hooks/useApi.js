import { useAuth } from "../contexts/AuthContext"
import axios from "axios"
import Place from "../model/place"

// TODO: Introduce model classes to return values where missing.

export function useApi() {
    const { accessToken } = useAuth()

    async function sendRequest(method, path, data = {}, args = {}) {
        const queryString = new URLSearchParams(Object.entries(args).filter(([_, v]) => v !== undefined)).toString()
        const url = import.meta.env.VITE_CORE_BASE_URL + path + (queryString ? "?" + queryString : "")

        try {
            const response = await axios({
                method,
                url,
                data: Object.keys(data).length ? data : undefined,
                headers: {
                    "Authorization": `Bearer ${accessToken.accessToken}`,
                    "Content-Type": "application/json",
                },
            })

            return response.data
        } catch (error) {
            return Promise.reject(error)
        }
    }

    async function createDevice(token) {
        return sendRequest("POST", "/devices",
            {
                type: "PORTAL",
                token: token
            })
    }

    async function getLabel(labelId) {
        return sendRequest("GET", "/labels/" + labelId)
    }

    async function listLabels() {
        return sendRequest("GET", "/labels")
    }

    async function updateLabelName(labelId, name) {
        return sendRequest("PATCH", "/labels/" + labelId,
            {
                name: name
            })
    }

    async function getHighlight(highlightId) {
        return sendRequest("GET", "/highlights/" + highlightId)
    }

    async function updateHighlightQualityAttributes(highlightId, composition, sky, shadows, circumstances, atmosphere) {
        return sendRequest("PATCH", "/highlights/" + highlightId,
            {
                attributes: {
                    composition: composition,
                    sky: sky,
                    shadows: shadows,
                    circumstances: circumstances,
                    atmosphere: atmosphere
                }
            })
    }

    async function createGeographicalCategory(name, country, category, radius, geoJson) {
        return sendRequest("POST", "/categories",
            {
                name: name,
                country: country,
                category: category,
                radius: radius,
                geoJson: geoJson
            },
            {
                type: "geographical"
            })
    }

    async function createGeographicalExtensionCategory(name, country, category, latitude, longitude) {
        return sendRequest("POST", "/categories",
            {
                name: name,
                country: country,
                category: category,
                latitude: latitude,
                longitude: longitude
            },
            {
                type: "geographicalExtension"
            })
    }

    async function createCompositeCategory(name, category, includedRegions, excludedRegions) {
        return sendRequest("POST", "/categories",
            {
                name: name,
                category: category,
                includedRegions: includedRegions,
                excludedRegions: excludedRegions
            },
            {
                type: "composite"
            })
    }

    async function listCategories({ country, categories, include } = {}) {
        return sendRequest("GET", "/categories", {},
            {
                country: country,
                categories: categories,
                include: include
            })
    }

    async function getCategory(categoryId) {
        return sendRequest("GET", "/categories/" + categoryId)
    }

    async function updateCategoryName(categoryId, name) {
        return sendRequest("PATCH", "/categories/" + categoryId,
            {
                name: name
            })
    }

    async function updateCategoryMetadata(categoryId, { unicode, color, publicHolidaysCalendar } = {}) {
        return sendRequest("PATCH", "/categories/" + categoryId,
            {
                metadata: {
                    unicode: unicode,
                    color: color,
                    publicHolidaysCalendar: publicHolidaysCalendar
                }
            })
    }

    async function updateCategoryMainHighlight(categoryId, mainHighlightId) {
        return sendRequest("PATCH", "/categories/" + categoryId,
            {
                mainHighlight: {
                    id: mainHighlightId
                }
            })
    }

    async function createCategoryHighlight(categoryId, photoId) {
        return sendRequest("POST", "/categories/" + categoryId + "/highlights",
            {
                photo: {
                    id: photoId
                }
            })
    }

    async function removeCategoryHighlight(categoryId, highlightId) {
        return sendRequest("DELETE", "/categories/" + categoryId + "/highlights/" + highlightId)
    }

    async function listConfigurationEntries() {
        return sendRequest("GET", "/configuration")
    }

    async function replaceConfigurationEntry(key, value) {
        return sendRequest("PUT", "/configuration/" + key,
            {
                value: value
            })
    }

    async function replaceFitness(timestamp, steps, seconds, calories, distance, forceOverwrite = false) {
        return sendRequest("PUT", "/fitness/" + timestamp + "?forceOverwrite=" + encodeURIComponent(forceOverwrite),
            {
                steps: steps,
                seconds: seconds,
                calories: calories,
                distance: distance
            })
    }

    async function getCoordinates(address) {
        return sendRequest("GET", "/coordinates", {},
            {
                address: address
            })
    }

    async function createEvent(name, args) {
        return sendRequest("POST", "/events",
            {
                name: name,
                args: args
            })
    }

    async function createCandidatePlace(name, address) {
        return sendRequest("POST", "/places?type=candidate&address=" + encodeURIComponent(address),
            {
                name: name
            })
            .then(place => new Place(place))
    }

    async function createPermanentPlace(name, address) {
        return sendRequest("POST", "/places?type=permanent&address=" + encodeURIComponent(address),
            {
                name: name
            })
            .then(place => new Place(place))
    }

    async function listRegularPlaces({ tripId, categoryId, labelId, year, albumId, photoId, minStart, maxEnd, include, sort } = {}) {
        return sendRequest("GET", "/places", {},
            {
                type: "regular",
                tripId: tripId,
                categoryId: categoryId,
                labelId: labelId,
                year: year,
                albumId: albumId,
                photoId: photoId,
                minStart: minStart,
                maxEnd: maxEnd,
                include: include,
                sort: sort
            })
            .then(places => places.map(place => new Place(place)))
    }

    async function listCandidatePlaces({ tripId, categoryId, labelId, include, sort } = {}) {
        return sendRequest("GET", "/places", {},
            {
                type: "candidate",
                tripId: tripId,
                categoryId: categoryId,
                labelId: labelId,
                include: include,
                sort: sort
            })
            .then(places => places.map(place => new Place(place)))
    }

    async function getPlace(placeId) {
        return sendRequest("GET", "/places/" + placeId)
            .then(place => new Place(place))
    }

    async function updatePlaceName(placeId, name) {
        return sendRequest("PATCH", "/places/" + placeId,
            {
                name: name
            })
            .then(place => new Place(place))
    }

    async function updatePlaceLocation(placeId, latitude, longitude) {
        return sendRequest("PATCH", "/places/" + placeId,
            {
                latitude: latitude,
                longitude: longitude
            })
            .then(place => new Place(place))
    }

    async function updatePlaceMainHighlight(placeId, mainHighlightId) {
        return sendRequest("PATCH", "/places/" + placeId,
            {
                mainHighlight: {
                    id: mainHighlightId
                }
            })
            .then(place => new Place(place))
    }

    async function updatePlaceExcerpt(placeId, excerpt) {
        return sendRequest("PATCH", "/places/" + placeId,
            {
                excerpt: excerpt
            })
            .then(place => new Place(place))
    }

    async function removeCandidatePlace(placeId) {
        return sendRequest("DELETE", "/places/" + placeId + "?type=candidate")
    }

    async function removePermanentPlace(placeId) {
        return sendRequest("DELETE", "/places/" + placeId + "?type=permanent")
    }

    async function createPlaceAlbum(placeId, timestamp) {
        return sendRequest("POST", "/places/" + placeId + "/albums?timestamp=" + timestamp)
    }

    async function refreshPlaceAlbum(placeId, albumId, { mainPhotoPosition } = {}) {
        return sendRequest("POST", "/places/" + placeId + "/albums/" + albumId + "/refresh", {},
            {
                mainPhotoPosition: mainPhotoPosition
            })
    }

    async function createPlaceAlbumPhoto(placeId, albumId, name, position, data) {
        return sendRequest("POST", "/places/" + placeId + "/albums/" + albumId + "/photos",
            {
                name: name,
                position: position,
                data: data
            })
    }

    async function listPlaceAlbumPhotos(placeId, albumId) {
        return sendRequest("GET", "/places/" + placeId + "/albums/" + albumId + "/photos")
    }

    async function createPlaceHighlight(placeId, photoId) {
        return sendRequest("POST", "/places/" + placeId + "/highlights",
            {
                photo: {
                    id: photoId
                }
            })
    }

    async function removePlaceHighlight(placeId, highlightId) {
        return sendRequest("DELETE", "/places/" + placeId + "/highlights/" + highlightId)
    }

    async function listDataConsistencyIssues() {
        return sendRequest("GET", "/inconsistencies")
    }

    async function listStatistics() {
        return sendRequest("GET", "/statistics")
    }

    async function createSubscription(description, value, currency, expiration) {
        return sendRequest("POST", "/subscriptions",
            {
                description: description,
                value: value,
                currency: currency,
                expiration: expiration
            })
    }

    async function listSubscriptions() {
        return sendRequest("GET", "/subscriptions")
    }

    async function createTimeTrackingEvent(type, hours, description, timestamp) {
        return sendRequest("POST", "/tracker",
            {
                type: type,
                hours: hours,
                description: description,
                timestamp: timestamp
            })
    }

    async function listTimeTrackingEvents({ type } = {}) {
        return sendRequest("GET", "/tracker", {},
            {
                type: type
            })
    }

    async function removeTimeTrackingEvent(eventId) {
        return sendRequest("DELETE", "/tracker/" + eventId)
    }

    async function listRegularTrips({ year, include } = {}) {
        return sendRequest("GET", "/trips", {},
            {
                type: "regular",
                year: year,
                include: include
            })
    }

    async function listCandidateTrips({ include } = {}) {
        return sendRequest("GET", "/trips", {},
            {
                type: "candidate",
                include: include
            })
    }

    async function getTrip(tripId) {
        return sendRequest("GET", "/trips/" + tripId)
    }

    async function updateTripName(tripId, name) {
        return sendRequest("PATCH", "/trips/" + tripId,
            {
                name: name
            })
    }

    async function updateTripStart(tripId, start) {
        return sendRequest("PATCH", "/trips/" + tripId,
            {
                start: start
            })
    }

    async function updateTripMainHighlight(tripId, mainHighlightId) {
        return sendRequest("PATCH", "/trips/" + tripId,
            {
                mainHighlight: {
                    id: mainHighlightId
                }
            })
    }

    async function replaceTrip(tripId, candidateTripId) {
        return sendRequest("PUT", "/trips/" + tripId,
            {
                id: candidateTripId
            })
    }

    async function removeTrip(tripId) {
        return sendRequest("DELETE", "/trips/" + tripId)
    }

    async function createTripExpense(tripId, type, description, value, currency, subscriptionId) {
        return sendRequest("POST", "/trips/" + tripId + "/expenses",
            {
                type: type,
                description: description,
                value: value,
                currency: currency,
                subscription: {
                    id: subscriptionId
                }
            })
    }

    async function updateTripExpenseDescription(tripId, expenseId, description) {
        return sendRequest("PATCH", "/trips/" + tripId + "/expenses/" + expenseId,
            {
                description: description
            })
    }

    async function updateTripExpenseValue(tripId, expenseId, value, currency) {
        return sendRequest("PATCH", "/trips/" + tripId + "/expenses/" + expenseId,
            {
                value: value,
                currency: currency
            })
    }

    async function removeTripExpense(tripId, expenseId) {
        return sendRequest("DELETE", "/trips/" + tripId + "/expenses/" + expenseId)
    }

    async function logFlight(flight, from, to, scheduledDeparture) {
        return sendRequest("POST", "/flights",
            {
                flight: flight,
                from: { name: from },
                to: { name: to },
                scheduledDeparture: scheduledDeparture
            },
            {
                type: "logged"
            })
    }

    async function logFlightManually(flight, aircraft, registration, from, fromCode, to, toCode,
        scheduledDeparture, actualDeparture, scheduledArrival, actualArrival) {
        return sendRequest("POST", "/flights",
            {
                flight: flight,
                aircraft: aircraft,
                registration: registration,
                from: { name: from, code: fromCode },
                to: { name: to, code: toCode },
                scheduledDeparture: scheduledDeparture,
                actualDeparture: actualDeparture,
                scheduledArrival: scheduledArrival,
                actualArrival: actualArrival
            },
            {
                type: "logged"
            })
    }

    async function createScheduledFlight(flight, from, to, scheduledDeparture, scheduledArrival) {
        return sendRequest("POST", "/flights",
            {
                flight: flight,
                from: { name: from },
                to: { name: to },
                scheduledDeparture: scheduledDeparture,
                scheduledArrival: scheduledArrival
            },
            {
                type: "scheduled"
            })
    }

    async function createWatchedFlight(flight, from, to, scheduledDeparture, scheduledArrival) {
        return sendRequest("POST", "/flights",
            {
                flight: flight,
                from: { name: from },
                to: { name: to },
                scheduledDeparture: scheduledDeparture,
                scheduledArrival: scheduledArrival
            },
            {
                type: "watched"
            })
    }

    async function createTripHighlight(tripId, photoId) {
        return sendRequest("POST", "/trips/" + tripId + "/highlights",
            {
                photo: {
                    id: photoId
                }
            })
    }

    async function removeTripHighlight(tripId, highlightId) {
        return sendRequest("DELETE", "/trips/" + tripId + "/highlights/" + highlightId)
    }

    async function createTripNote(tripId, content) {
        return sendRequest("POST", "/trips/" + tripId + "/notes",
            {
                content: content
            })
    }

    async function createPlaceNote(placeId, content) {
        return sendRequest("POST", "/places/" + placeId + "/notes",
            {
                content: content
            })
    }

    async function createPlaceLabel(placeId, name) {
        return sendRequest("POST", "/places/" + placeId + "/labels",
            {
                name: name
            })
    }

    async function removeTripNote(tripId, noteId) {
        return sendRequest("DELETE", "/trips/" + tripId + "/notes/" + noteId)
    }

    async function removePlaceNote(placeId, noteId) {
        return sendRequest("DELETE", "/places/" + placeId + "/notes/" + noteId)
    }

    async function removePlaceLabel(placeId, labelId) {
        return sendRequest("DELETE", "/places/" + placeId + "/labels/" + labelId)
    }

    async function createAirlineCode(airlineId, code) {
        return sendRequest("POST", "/airlines/" + airlineId + "/codes",
            {
                code: code
            })
    }

    async function removeAirlineCode(airlineId, code) {
        return sendRequest("DELETE", "/airlines/" + airlineId + "/codes/" + code)
    }

    async function createAirline(name, { logo } = {}) {
        return sendRequest("POST", "/airlines",
            {
                name: name,
                logo: logo
            })
    }

    async function listAirlines() {
        return sendRequest("GET", "/airlines")
    }

    async function getAirline(airlineId) {
        return sendRequest("GET", "/airlines/" + airlineId)
    }

    async function removeAirline(airlineId) {
        return sendRequest("DELETE", "/airlines/" + airlineId)
    }

    async function updateAirlineName(airlineId, name) {
        return sendRequest("PATCH", "/airlines/" + airlineId,
            {
                name: name
            })
    }

    async function updateAirlineLogo(airlineId, logo) {
        return sendRequest("PATCH", "/airlines/" + airlineId,
            {
                name: logo
            })
    }

    async function listYears({ include } = {}) {
        return sendRequest("GET", "/years", {},
            {
                include: include
            })
    }

    async function getYear(year) {
        return sendRequest("GET", "/years/" + year)
    }

    async function updateYearMainHighlight(year, mainHighlightId) {
        return sendRequest("PATCH", "/years/" + year,
            {
                mainHighlight: {
                    id: mainHighlightId
                }
            })
    }

    async function createYearHighlight(year, photoId) {
        return sendRequest("POST", "/years/" + year + "/highlights",
            {
                photo: {
                    id: photoId
                }
            })
    }

    async function removeYearHighlight(year, highlightId) {
        return sendRequest("DELETE", "/years/" + year + "/highlights/" + highlightId)
    }

    return {
        createAirlineCode,
        removeAirlineCode,
        createDevice,
        getLabel,
        listLabels,
        updateLabelName,
        getHighlight,
        updateHighlightQualityAttributes,
        createGeographicalCategory,
        createGeographicalExtensionCategory,
        createCompositeCategory,
        listCategories,
        getCategory,
        updateCategoryName,
        updateCategoryMetadata,
        updateCategoryMainHighlight,
        createCategoryHighlight,
        removeCategoryHighlight,
        listConfigurationEntries,
        replaceConfigurationEntry,
        getCoordinates,
        createEvent,
        createCandidatePlace,
        createPermanentPlace,
        listRegularPlaces,
        listCandidatePlaces,
        getPlace,
        updatePlaceName,
        updatePlaceLocation,
        updatePlaceMainHighlight,
        updatePlaceExcerpt,
        removeCandidatePlace,
        removePermanentPlace,
        createPlaceAlbum,
        refreshPlaceAlbum,
        createPlaceAlbumPhoto,
        listPlaceAlbumPhotos,
        createPlaceHighlight,
        removePlaceHighlight,
        createPlaceNote,
        removePlaceNote,
        createPlaceLabel,
        removePlaceLabel,
        listDataConsistencyIssues,
        listStatistics,
        createSubscription,
        listSubscriptions,
        createTimeTrackingEvent,
        listTimeTrackingEvents,
        removeTimeTrackingEvent,
        listRegularTrips,
        listCandidateTrips,
        getTrip,
        updateTripName,
        updateTripStart,
        updateTripMainHighlight,
        replaceTrip,
        removeTrip,
        createTripExpense,
        updateTripExpenseDescription,
        updateTripExpenseValue,
        removeTripExpense,
        logFlight,
        logFlightManually,
        createScheduledFlight,
        createWatchedFlight,
        createTripHighlight,
        removeTripHighlight,
        createTripNote,
        removeTripNote,
        createAirline,
        listAirlines,
        getAirline,
        updateAirlineLogo,
        updateAirlineName,
        removeAirline,
        listYears,
        getYear,
        updateYearMainHighlight,
        createYearHighlight,
        removeYearHighlight,
        replaceFitness
    }
}