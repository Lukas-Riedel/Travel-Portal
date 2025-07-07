import { useAuth } from "../contexts/AuthContext"
import axios from "axios"
import Place from "../model/place"

// TODO: Introduce model classes to return values where missing.

export function useApi() {
    const { accessToken } = useAuth()

    async function sendRequest(method, path, data = {}, args = {}) {
        const queryString = new URLSearchParams(Object.entries(args).filter(([_, v]) => v !== undefined)).toString()
        const url = import.meta.env.VITE_SERVICE_BASE_URL + path + (queryString ? "?" + queryString : "")

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

    async function getHighlight(highlightId) {
        return sendRequest("GET", "/highlights/" + highlightId)
    }

    async function updateHighlightQualityAttributes(highlightId, composition, sky, shadows, circumstances) {
        return sendRequest("PATCH", "/highlights/" + highlightId,
            {
                composition: composition,
                sky: sky,
                shadows: shadows,
                circumstances: circumstances
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
            })
    }

    async function createCompositeCategory(name, category, includedRegions, excludedRegions) {
        return sendRequest("POST", "/categories",
            {
                name: name,
                category: category,
                includedRegions: includedRegions,
                excludedRegions: excludedRegions
            })
    }

    async function listCategories({ categories, include } = {}) {
        return sendRequest("GET", "/categories", {},
            {
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

    async function updateCategoryMainHighlight(categoryId, mainHighlightId) {
        return sendRequest("PATCH", "/categories/" + categoryId,
            {
                mainHighlightId: mainHighlightId
            })
    }

    async function createCategoryHighlight(categoryId, photoId) {
        return sendRequest("POST", "/categories/" + categoryId + "/highlights",
            {
                photoId: photoId
            })
    }

    async function removeCategoryHighlight(categoryId, highlightId) {
        return sendRequest("DELETE", "/categories/" + categoryId + "/highlights/" + highlightId)
    }

    async function listConfigurationEntries(levels) {
        return sendRequest("GET", "/configuration", {},
            {
                levels: levels
            })
    }

    async function updateConfigurationEntry(type, key, value) {
        return sendRequest("PATCH", "/configuration/" + type,
            {
                key: key,
                value: value
            })
    }

    async function getCoordinates(address) {
        return sendRequest("GET", "/coordinates/" + address)
    }

    async function createEvent(name, args) {
        return sendRequest("POST", "/events",
            {
                name: name,
                args: args
            })
    }

    async function listEvents(name) {
        return sendRequest("GET", "/events?name=" + name)
    }

    async function removeEvent(eventId) {
        return sendRequest("DELETE", "/events/" + eventId)
    }

    async function createCandidatePlace(name, address) {
        return sendRequest("POST", "/places",
            {
                type: "candidate",
                name: name,
                address: address
            })
            .then(place => new Place(place))
    }

    async function createPermanentPlace(name, address) {
        return sendRequest("POST", "/places",
            {
                type: "permanent",
                name: name,
                address: address
            })
            .then(place => new Place(place))
    }

    async function listRegularPlaces({ tripId, categoryId, labelName, year, minStart, maxEnd, include, sort } = {}) {
        return sendRequest("GET", "/places", {},
            {
                type: "regular",
                tripId: tripId,
                categoryId: categoryId,
                label: labelName,
                year: year,
                minStart: minStart,
                maxEnd: maxEnd,
                include: include,
                sort: sort
            })
            .then(places => places.map(place => new Place(place)))
    }

    async function listCandidatePlaces({ tripId, categoryId, include, sort } = {}) {
        return sendRequest("GET", "/places", {},
            {
                type: "candidate",
                tripId: tripId,
                categoryId: categoryId,
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
                mainHighlightId: mainHighlightId
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
        return sendRequest("DELETE", "/places/" + placeId, {},
            {
                type: "candidate"
            })
    }

    async function removePermanentPlace(placeId) {
        return sendRequest("DELETE", "/places/" + placeId, {},
            {
                type: "permanent"
            })
    }

    async function createPlaceAlbum(placeId, timestamp) {
        return sendRequest("POST", "/places/" + placeId + "/albums",
            {
                timestamp: timestamp
            })
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
                photoId: photoId
            })
    }

    async function removePlaceHighlight(placeId, highlightId) {
        return sendRequest("DELETE", "/places/" + placeId + "/highlights/" + highlightId)
    }

    async function listProblems() {
        return sendRequest("GET", "/problems")
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

    async function createTimeTrackingEvent(type, hours, description, date) {
        return sendRequest("POST", "/tracker",
            {
                type: type,
                hours: hours,
                description: description,
                date: date
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
                mainHighlightId: mainHighlightId
            })
    }

    async function replaceTrip(tripId, candidateTripId) {
        return sendRequest("PUT", "/trips/" + tripId,
            {
                candidateTripId: candidateTripId
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
                subscriptionId: subscriptionId
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

    async function logFlight(tripId, flight, from, to, scheduledDeparture) {
        return sendRequest("POST", "/flights/log?tripId=" + tripId,
            {
                flight: flight,
                from: from,
                to: to,
                scheduledDeparture: scheduledDeparture
            })
    }

    async function logFlightManually(tripId, flight, aircraft, registration, from, fromCode, to, toCode,
        scheduledDeparture, actualDeparture, scheduledArrival, actualArrival) {
        return sendRequest("POST", "/flights/log?tripId=" + tripId,
            {
                flight: flight,
                aircraft: aircraft,
                registration: registration,
                from: from,
                fromCode: fromCode,
                to: to,
                toCode: toCode,
                scheduledDeparture: scheduledDeparture,
                actualDeparture: actualDeparture,
                scheduledArrival: scheduledArrival,
                actualArrival: actualArrival
            })
    }

    async function createFlight(flight, from, to, scheduledDeparture, scheduledArrival) {
        return sendRequest("POST", "/flights",
            {
                flight: flight,
                from: from,
                to: to,
                scheduledDeparture: scheduledDeparture,
                scheduledArrival: scheduledArrival
            })
    }

    async function createTripHighlight(tripId, photoId) {
        return sendRequest("POST", "/trips/" + tripId + "/highlights",
            {
                photoId: photoId
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

    async function createPlaceLabel(placeId, name) {
        return sendRequest("POST", "/places/" + placeId + "/labels",
            {
                name: name
            })
    }

    async function removeTripNote(tripId, noteId) {
        return sendRequest("DELETE", "/trips/" + tripId + "/notes/" + noteId)
    }

    async function removePlaceLabel(placeId, labelId) {
        return sendRequest("DELETE", "/places/" + placeId + "/labels/" + labelId)
    }

    async function listAirlines() {
        return sendRequest("GET", "/airlines", {}, {})
    }

    async function getAirline(airlineCode) {
        return sendRequest("GET", "/airlines/" + airlineCode, {}, {})
    }

    async function updateAirlineName(airlineCode, name) {
        return sendRequest("PATCH", "/airlines/" + airlineCode,
            {
                name: name
            })
    }

    async function updateAirlineLogo(airlineCode, logo) {
        return sendRequest("PATCH", "/airlines/" + airlineCode,
            {
                name: logo
            })
    }

    async function listYears(include) {
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
                mainHighlightId: mainHighlightId
            })
    }

    async function createYearHighlight(year, photoId) {
        return sendRequest("POST", "/years/" + year + "/highlights",
            {
                photoId: photoId
            })
    }

    async function removeYearHighlight(year, highlightId) {
        return sendRequest("DELETE", "/years/" + year + "/highlights/" + highlightId)
    }

    return {
        getHighlight,
        updateHighlightQualityAttributes,
        createGeographicalCategory,
        createGeographicalExtensionCategory,
        createCompositeCategory,
        listCategories,
        getCategory,
        updateCategoryName,
        updateCategoryMainHighlight,
        createCategoryHighlight,
        removeCategoryHighlight,
        listConfigurationEntries,
        updateConfigurationEntry,
        getCoordinates,
        createEvent,
        listEvents,
        removeEvent,
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
        listProblems,
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
        createFlight,
        createTripHighlight,
        removeTripHighlight,
        createTripNote,
        createPlaceLabel,
        removeTripNote,
        removePlaceLabel,
        listAirlines,
        getAirline,
        updateAirlineLogo,
        updateAirlineName,
        listYears,
        getYear,
        updateYearMainHighlight,
        createYearHighlight,
        removeYearHighlight
    }
}