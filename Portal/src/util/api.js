import axios from "axios"
import Place from "../model/place"

// TODO: Introduce model classes to return values where missing.

export async function createGeographicalCategory(name, country, category, radius, geoJson) {
    return sendRequest("POST", "/categories",
        {
            name: name,
            country: country,
            category: category,
            radius: radius,
            geoJson: geoJson
        })
}

export async function createGeographicalExtensionCategory(name, country, category, latitude, longitude) {
    return sendRequest("POST", "/categories",
        {
            name: name,
            country: country,
            category: category,
            latitude: latitude,
            longitude: longitude
        })
}

export async function createCompositeCategory(name, category, includedRegions, excludedRegions) {
    return sendRequest("POST", "/categories",
        {
            name: name,
            category: category,
            includedRegions: includedRegions,
            excludedRegions: excludedRegions
        })
}

export async function listCategories(categories = undefined, include = undefined) {
    return sendRequest("GET", "/categories", {},
        {
            categories: categories,
            include: include
        })
}

export async function getCategory(categoryId) {
    return sendRequest("GET", "/categories/" + categoryId)
}

export async function updateCategoryName(categoryId, name) {
    return sendRequest("PATCH", "/categories/" + categoryId,
        {
            name: name
        })
}

export async function updateCategoryMainHighlight(categoryId, mainHighlightId) {
    return sendRequest("PATCH", "/categories/" + categoryId,
        {
            mainHighlightId: mainHighlightId
        })
}

export async function createCategoryHighlight(categoryId, photoId) {
    return sendRequest("POST", "/categories/" + categoryId + "/highlights",
        {
            photoId: photoId
        })
}

export async function listConfigurationEntries(levels) {
    return sendRequest("GET", "/configuration", {},
        {
            levels: levels
        })
}

export async function updateConfigurationEntry(type, key, value) {
    return sendRequest("PATCH", "/configuration/" + type,
        {
            key: key,
            value: value
        })
}

export async function getCoordinates(address) {
    return sendRequest("GET", "/coordinates/" + address)
}

export async function createEvent(name, args) {
    return sendRequest("POST", "/events",
        {
            name: name,
            args: args
        })
}

export async function listEvents(name) {
    return sendRequest("GET", "/events?name=" + name)
}

export async function removeEvent(eventId) {
    return sendRequest("DELETE", "/events/" + eventId)
}

export async function createCandidatePlace(name, address) {
    return sendRequest("POST", "/places",
        {
            type: "candidate",
            name: name,
            address: address
        })
        .then(place => new Place(place))
}

export async function createPermanentPlace(name, address) {
    return sendRequest("POST", "/places",
        {
            type: "permanent",
            name: name,
            address: address
        })
        .then(place => new Place(place))
}

export async function listRegularPlaces(tripId = undefined, categoryId = undefined, label = undefined,
    year = undefined, minStart = undefined, maxEnd = undefined, include = undefined) {
    return sendRequest("GET", "/places", {},
        {
            type: "regular",
            tripId: tripId,
            categoryId: categoryId,
            label: label,
            year: year,
            minStart: minStart,
            maxEnd: maxEnd,
            include: include
        })
        .then(places => places.map(place => new Place(place)))
}

export async function listCandidatePlaces(tripId = undefined, categoryId = undefined, include = undefined) {
    return sendRequest("GET", "/places", {},
        {
            type: "candidate",
            tripId: tripId,
            categoryId: categoryId,
            include: include
        })
        .then(places => places.map(place => new Place(place)))
}

export async function getPlace(placeId) {
    return sendRequest("GET", "/places/" + placeId)
        .then(place => new Place(place))
}

export async function updatePlaceName(placeId, name) {
    return sendRequest("PATCH", "/places/" + placeId,
        {
            name: name
        })
        .then(place => new Place(place))
}

export async function updatePlaceLocation(placeId, latitude, longitude) {
    return sendRequest("PATCH", "/places/" + placeId,
        {
            latitude: latitude,
            longitude: longitude
        })
        .then(place => new Place(place))
}

export async function updatePlaceMainHighlight(placeId, mainHighlightId) {
    return sendRequest("PATCH", "/places/" + placeId,
        {
            mainHighlightId: mainHighlightId
        })
        .then(place => new Place(place))
}

export async function updatePlaceExcerpt(placeId, excerpt) {
    return sendRequest("PATCH", "/places/" + placeId,
        {
            excerpt: excerpt
        })
        .then(place => new Place(place))
}

export async function removeCandidatePlace(placeId) {
    return sendRequest("DELETE", "/places/" + placeId, {},
        {
            type: "candidate"
        })
}

export async function removePermanentPlace(placeId) {
    return sendRequest("DELETE", "/places/" + placeId, {},
        {
            type: "permanent"
        })
}

export async function createPlaceAlbum(placeId, timestamp) {
    return sendRequest("POST", "/places/" + placeId + "/albums",
        {
            timestamp: timestamp
        })
}

export async function refreshPlaceAlbum(placeId, albumId, mainPhotoPosition = undefined) {
    return sendRequest("POST", "/places/" + placeId + "/albums/" + albumId + "/refresh", {},
        {
            mainPhotoPosition: mainPhotoPosition
        })
}

export async function createPlaceAlbumPhoto(placeId, albumId, name, position, data) {
    return sendRequest("POST", "/places/" + placeId + "/albums/" + albumId + "/photos",
        {
            name: name,
            position: position,
            data: data
        })
}

export async function listPlaceAlbumPhotos(placeId, albumId) {
    return sendRequest("GET", "/places/" + placeId + "/albums/" + albumId + "/photos")
}

export async function createPlaceHighlight(placeId, photoId) {
    return sendRequest("POST", "/places/" + placeId + "/highlights",
        {
            photoId: photoId
        })
}

export async function listProblems() {
    return sendRequest("GET", "/problems")
}

export async function listStatistics() {
    return sendRequest("GET", "/statistics")
}

export async function createSubscription(description, value, currency, expiration) {
    return sendRequest("POST", "/subscriptions",
        {
            description: description,
            value: value,
            currency: currency,
            expiration: expiration
        })
}

export async function listSubscriptions() {
    return sendRequest("GET", "/subscriptions")
}

export async function createTimeTrackingEvent(type, hours, description, date) {
    return sendRequest("POST", "/tracker",
        {
            type: type,
            hours: hours,
            description: description,
            date: date
        })
}

export async function listTimeTrackingEvents(type = undefined) {
    return sendRequest("GET", "/tracker", {},
        {
            type: type
        })
}

export async function removeTimeTrackingEvent(eventId) {
    return sendRequest("DELETE", "/tracker/" + eventId)
}

export async function listTrips(year = undefined, include = undefined) {
    return sendRequest("GET", "/trips", {},
        {
            type: "regular",
            year: year,
            include: include
        })
}

export async function listCandidateTrips(include = undefined) {
    return sendRequest("GET", "/trips", {},
        {
            type: "candidate",
            include: include
        })
}

export async function getTrip(tripId) {
    return sendRequest("GET", "/trips/" + tripId)
}

export async function updateTripName(tripId, name) {
    return sendRequest("PATCH", "/trips/" + tripId,
        {
            name: name
        })
}

export async function updateTripStart(tripId, start) {
    return sendRequest("PATCH", "/trips/" + tripId,
        {
            start: start
        })
}

export async function updateTripMainHighlight(tripId, mainHighlightId) {
    return sendRequest("PATCH", "/trips/" + tripId,
        {
            mainHighlightId: mainHighlightId
        })
}

export async function replaceTrip(tripId, candidateTripId) {
    return sendRequest("PUT", "/trips/" + tripId,
        {
            candidateTripId: candidateTripId
        })
}

export async function removeTrip(tripId) {
    return sendRequest("DELETE", "/trips/" + tripId)
}

export async function createTripExpense(tripId, type, description, value, currency) {
    return sendRequest("POST", "/trips/" + tripId + "/expenses",
        {
            type: type,
            description: description,
            value: value,
            currency: currency
        })
}

export async function createTripExpenseWithSubscription(tripId, type, description, value, currency, subscriptionId) {
    return sendRequest("POST", "/trips/" + tripId + "/expenses",
        {
            type: type,
            description: description,
            value: value,
            currency: currency,
            subscriptionId: subscriptionId
        })
}

export async function updateTripExpenseDescription(tripId, expenseId, description) {
    return sendRequest("PATCH", "/trips/" + tripId + "/expenses/" + expenseId,
        {
            description: description
        })
}

export async function updateTripExpenseValue(tripId, expenseId, value, currency) {
    return sendRequest("PATCH", "/trips/" + tripId + "/expenses/" + expenseId,
        {
            value: value,
            currency: currency
        })
}

export async function removeTripExpense(tripId, expenseId) {
    return sendRequest("DELETE", "/trips/" + tripId + "/expenses/" + expenseId)
}

export async function logFlight(tripId, flight, from, to, scheduledDeparture) {
    return sendRequest("POST", "/flights/log?tripId=" + tripId,
        {
            flight: flight,
            from: from,
            to: to,
            scheduledDeparture: scheduledDeparture
        })
}

export async function logFlightManually(tripId, flight, aircraft, registration, from, fromCode, to, toCode,
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

export async function createFlight(flight, from, to, scheduledDeparture, scheduledArrival) {
    return sendRequest("POST", "/flights",
        {
            flight: flight,
            from: from,
            to: to,
            scheduledDeparture: scheduledDeparture,
            scheduledArrival: scheduledArrival
        })
}

export async function createTripHighlight(tripId, photoId) {
    return sendRequest("POST", "/trips/" + tripId + "/highlights",
        {
            photoId: photoId
        })
}

export async function createTripNote(tripId, content) {
    return sendRequest("POST", "/trips/" + tripId + "/notes",
        {
            content: content
        })
}

export async function createPlaceLabel(placeId, name) {
    return sendRequest("POST", "/places/" + placeId + "/labels",
        {
            name: name
        })
}

export async function removeTripNote(tripId, noteId) {
    return sendRequest("DELETE", "/trips/" + tripId + "/notes/" + noteId)
}

export async function removePlaceLabel(placeId, labelId) {
    return sendRequest("DELETE", "/places/" + placeId + "/labels/" + labelId)
}

export async function listYears(include) {
    return sendRequest("GET", "/years", {},
        {
            include: include
        })
}

export async function getYear(year) {
    return sendRequest("GET", "/years/" + year)
}

export async function updateYearMainHighlight(year, mainHighlightId) {
    return sendRequest("PATCH", "/year/" + year,
        {
            mainHighlightId: mainHighlightId
        })
}

export async function createYearHighlight(year, photoId) {
    return sendRequest("POST", "/years/" + year + "/highlights",
        {
            photoId: photoId
        })
}

async function sendRequest(method, url, data = {}, args = {}) {
    const argKeys = Object.keys(args).filter(arg => args[arg] !== undefined)
    const queryString = argKeys.length === 0 ? "" : ("?" + argKeys.map(key => key + "=" + args[key]).join("&"))

    try {
        const token = await getBearerToken()
        const response = await axios({
            method,
            url: "/api" + url + queryString,
            data: Object.keys(data).length ? data : undefined,
            headers: {
                "Authorization": "Bearer " + token,
                "Content-Type": "application/json",
            },
        })

        return response.data
    } catch (error) {
        return Promise.reject(error)
    }
}

async function getBearerToken() {
    // TODO: Cache access and refresh tokens for future use
    // TODO: Utilize refresh token to obtain a new access token

    try {
        const response = await axios.post("/api/iam",
            {
                username: "guest",
                password: "guest"
            },
            {
                headers: { "Content-Type": "application/json" }
            })

        return response.data.accessToken
    }
    catch (e) {
        return Promise.reject(e)
    }
}