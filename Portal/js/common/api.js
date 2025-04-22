class Api {

    #hostName;

    constructor(hostName) {
        this.#hostName = hostName;
    }

    async createGeographicalCategory(name, country, category, radius, geoJson) {
        return this.#sendRequest("POST", "categories",
            {
                name: name,
                country: country,
                category: category,
                radius: radius,
                geoJson: geoJson
            });
    }

    async createGeographicalExtensionCategory(name, country, category, latitude, longitude) {
        return this.#sendRequest("POST", "categories",
            {
                name: name,
                country: country,
                category: category,
                latitude: latitude,
                longitude: longitude
            });
    }

    async createCompositeCategory(name, category, includedRegions, excludedRegions) {
        return this.#sendRequest("POST", "categories",
            {
                name: name,
                category: category,
                includedRegions: includedRegions,
                excludedRegions: excludedRegions
            });
    }

    async listCategories(categories = undefined, include = undefined) {
        return this.#sendRequest("GET", "categories", {},
            {
                categories: categories,
                include: include
            });
    }

    async getCategory(categoryId) {
        return this.#sendRequest("GET", "categories/" + categoryId);
    }

    async updateCategoryName(categoryId, name) {
        return this.#sendRequest("PATCH", "categories/" + categoryId, 
            {
                name: name
            });
    }

    async updateCategoryMainHighlight(categoryId, mainHighlightId) {
        return this.#sendRequest("PATCH", "categories/" + categoryId, 
            {
                mainHighlightId: mainHighlightId
            });
    }

    async createCategoryHighlight(categoryId, photoId) {
        return this.#sendRequest("POST", "categories/" + categoryId + "/highlights", 
            {
                photoId: photoId
            });
    }

    async listConfigurationEntries(levels) {
        return this.#sendRequest("GET", "configuration", {},
            {
                levels: levels
            });
    }

    async updateConfigurationEntry(type, key, value) {
        return this.#sendRequest("PATCH", "configuration/" + type, 
            {
                key: key,
                value: value
            });
    }

    async getCoordinates(address) {
        return this.#sendRequest("GET", "coordinates/" + address);
    }

    async createEvent(name, args) {
        return this.#sendRequest("POST", "events", 
            {
                name: name,
                args: args
            });
    }

    async listEvents(name) {
        return this.#sendRequest("GET", "events?name=" + name);
    }

    async removeEvent(eventId) {
        return this.#sendRequest("DELETE", "events/" + eventId);
    }

    async createCandidatePlace(name, address) {
        return this.#sendRequest("POST", "places", 
            {
                type: "candidate",
                name: name,
                address: address
            });
    }

    async createPermanentPlace(name, address) {
        return this.#sendRequest("POST", "places", 
            {
                type: "permanent",
                name: name,
                address: address
            });
    }

    async listRegularPlaces(tripId = undefined, categoryId = undefined, label = undefined, year = undefined, minStart = undefined, maxEnd = undefined,
        include = undefined) {
        return this.#sendRequest("GET", "places", {}, 
            {
                type: "regular",
                tripId: tripId,
                categoryId: categoryId,
                label: label,
                year: year,
                minStart: minStart,
                maxEnd: maxEnd,
                include : include
            });
    }

    async listCandidatePlaces(tripId = undefined, categoryId = undefined, include = undefined) {
        return this.#sendRequest("GET", "places", {}, 
            {
                type: "candidate",
                tripId: tripId,
                categoryId: categoryId,
                include: include
            });
    }

    async getPlace(placeId) {
        return this.#sendRequest("GET", "places/" + placeId);
    }

    async updatePlaceName(placeId, name) {
        return this.#sendRequest("PATCH", "places/" + placeId, 
            {
                name: name
            });
    }

    async updatePlaceLocation(placeId, latitude, longitude) {
        return this.#sendRequest("PATCH", "places/" + placeId, 
            {
                latitude: latitude,
                longitude: longitude
            });
    }

    async updatePlaceMainHighlight(placeId, mainHighlightId) {
        return this.#sendRequest("PATCH", "places/" + placeId, 
            {
                mainHighlightId: mainHighlightId
            });
    }

    async updatePlaceExcerpt(placeId, excerpt) {
        return this.#sendRequest("PATCH", "places/" + placeId, 
            {
                excerpt: excerpt
            });
    }

    async removeCandidatePlace(placeId) {
        return this.#sendRequest("DELETE", "places/" + placeId, {}, 
            {
                type: "candidate"
            });
    }

    async removePermanentPlace(placeId) {
        return this.#sendRequest("DELETE", "places/" + placeId, {}, 
            {
                type: "permanent"
            });
    }

    async createPlaceAlbum(placeId, timestamp) {
        return this.#sendRequest("POST", "places/" + placeId + "/albums", 
            {
                timestamp: timestamp
            });
    }

    async refreshPlaceAlbum(placeId, albumId, mainPhotoPosition = undefined) {
        return this.#sendRequest("POST", "places/" + placeId + "/albums/" + albumId + "/refresh", {}, 
            {
                mainPhotoPosition: mainPhotoPosition
            });
    }

    async createPlaceAlbumPhoto(placeId, albumId, name, position, data) {
        return this.#sendRequest("POST", "places/" + placeId + "/albums/" + albumId + "/photos", 
            {
                name: name,
                position: position,
                data: data
            });
    }

    async listPlaceAlbumPhotos(placeId, albumId) {
        return this.#sendRequest("GET", "places/" + placeId + "/albums/" + albumId + "/photos");
    }

    async createPlaceHighlight(placeId, photoId) {
        return this.#sendRequest("POST", "places/" + placeId + "/highlights", 
            {
                photoId: photoId
            });
    }

    async listProblems() {
        return this.#sendRequest("GET", "problems");
    }

    async listStatistics() {
        return this.#sendRequest("GET", "statistics");
    }

    async createSubscription(description, value, currency, expiration) {
        return this.#sendRequest("POST", "subscriptions", 
            {
                description: description,
                value: value,
                currency: currency,
                expiration: expiration
            });
    }

    async listSubscriptions() {
        return this.#sendRequest("GET", "subscriptions");
    }

    async createTimeTrackingEvent(type, hours, description, date) {
        return this.#sendRequest("POST", "tracker", 
            {
                type: type,
                hours: hours,
                description: description,
                date: date
            });
    }

    async listTimeTrackingEvents(type = undefined) {
        return this.#sendRequest("GET", "tracker", {}, 
            {
                type: type
            });
    }

    async removeTimeTrackingEvent(eventId) {
        return this.#sendRequest("DELETE", "tracker/" + eventId);
    }

    async listTrips(year = undefined, include = undefined) {
        return this.#sendRequest("GET", "trips", {}, 
            {
                type: "regular",
                year: year,
                include: include
            });
    }

    async listCandidateTrips(include = undefined) {
        return this.#sendRequest("GET", "trips", {}, 
            {
                type: "candidate",
                include: include
            });
    }

    async getTrip(tripId) {
        return this.#sendRequest("GET", "trips/" + tripId);
    }

    async updateTripName(tripId, name) {
        return this.#sendRequest("PATCH", "trips/" + tripId, 
            {
                name: name
            });
    }

    async updateTripStart(tripId, start) {
        return this.#sendRequest("PATCH", "trips/" + tripId, 
            {
                start: start
            });
    }

    async updateTripMainHighlight(tripId, mainHighlightId) {
        return this.#sendRequest("PATCH", "trips/" + tripId, 
            {
                mainHighlightId: mainHighlightId
            });
    }

    async replaceTrip(tripId, candidateTripId) {
        return this.#sendRequest("PUT", "trips/" + tripId, 
            {
                candidateTripId: candidateTripId
            });
    }

    async removeTrip(tripId) {
        return this.#sendRequest("DELETE", "trips/" + tripId);
    }

    async createTripExpense(tripId, type, description, value, currency) {
        return this.#sendRequest("POST", "trips/" + tripId + "/expenses", 
            {
                type: type,
                description: description,
                value: value,
                currency: currency
            });
    }

    async createTripExpenseWithSubscription(tripId, type, description, value, currency, subscriptionId) {
        return this.#sendRequest("POST", "trips/" + tripId + "/expenses", 
            {
                type: type,
                description: description,
                value: value,
                currency: currency,
                subscriptionId: subscriptionId
            });
    }

    async updateTripExpenseDescription(tripId, expenseId, description) {
        return this.#sendRequest("PATCH", "trips/" + tripId + "/expenses/" + expenseId, 
            {
                description: description
            });
    }

    async updateTripExpenseValue(tripId, expenseId, value, currency) {
        return this.#sendRequest("PATCH", "trips/" + tripId + "/expenses/" + expenseId, 
            {
                value: value,
                currency: currency
            });
    }

    async removeTripExpense(tripId, expenseId) {
        return this.#sendRequest("DELETE", "trips/" + tripId + "/expenses/" + expenseId);
    }

    async logFlight(tripId, flight, from, to, scheduledDeparture) {
        return this.#sendRequest("POST", "flights/log?tripId=" + tripId, 
            {
                flight: flight,
                from: from,
                to: to,
                scheduledDeparture: scheduledDeparture
            });
    }

    async logFlightManually(tripId, flight, aircraft, registration, from, fromCode, to, toCode,
        scheduledDeparture, actualDeparture, scheduledArrival, actualArrival) {
        return this.#sendRequest("POST", "flights/log?tripId=" + tripId, 
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
            });
    }

    async createFlight(flight, from, to, scheduledDeparture, scheduledArrival) {
        return this.#sendRequest("POST", "flights", 
            {
                flight: flight,
                from: from,
                to: to,
                scheduledDeparture: scheduledDeparture,
                scheduledArrival: scheduledArrival
            });
    }

    async createTripHighlight(tripId, photoId) {
        return this.#sendRequest("POST", "trips/" + tripId + "/highlights", 
            {
                photoId: photoId
            });
    }

    async createTripNote(tripId, content) {
        return this.#sendRequest("POST", "trips/" + tripId + "/notes", 
            {
                content: content
            });
    }

    async createPlaceLabel(placeId, name) {
        return this.#sendRequest("POST", "places/" + placeId + "/labels", 
            {
                name: name
            });
    }

    async removeTripNote(tripId, noteId) {
        return this.#sendRequest("DELETE", "trips/" + tripId + "/notes/" + noteId);
    }

    async removePlaceLabel(placeId, labelId) {
        return this.#sendRequest("DELETE", "places/" + placeId + "/labels/" + labelId);
    }

    async listYears(include) {
        return this.#sendRequest("GET", "years", {}, 
            {
                include: include
            });
    }

    async getYear(year) {
        return this.#sendRequest("GET", "years/" + year);
    }

    async updateYearMainHighlight(year, mainHighlightId) {
        return this.#sendRequest("PATCH", "year/" + year, 
            {
                mainHighlightId: mainHighlightId
            });
    }

    async createYearHighlight(year, photoId) {
        return this.#sendRequest("POST", "years/" + year + "/highlights", 
            {
                photoId: photoId
            });
    }

    async #sendRequest(method, url, data = {}, args = {}) {
        const argKeys = Object.keys(args).filter(arg => args[arg] !== undefined);
        const queryString = argKeys.length === 0 ? "" : ("?" + argKeys.map(key => key + "=" + args[key]).join("&"));

        return new Promise(async (resolve, reject) => {
            $.ajax({
                method: method,
                url: "https://" + this.#hostName + "/" + url + queryString,
                data: Object.keys(data).length ? JSON.stringify(data) : undefined,
                dataType: "json",
                headers: {
                    "Authorization": "Bearer " + (await this.#getBearerToken())
                },
                success: resolve,
                error: reject
            });
        });
    }

    async #getBearerToken() {
        const cachedBearerToken = document.cookie.match("(^|;)\\s*accessToken\\s*=\\s*([^;]+)")?.pop();
        if (cachedBearerToken !== undefined) {
            return JSON.parse(decodeURIComponent(cachedBearerToken)).accessToken;
        }
        
        const cachedRefreshToken = document.cookie.match("(^|;)\\s*refreshToken\\s*=\\s*([^;]+)")?.pop();
        if (cachedRefreshToken !== undefined) {
            try {                
                const response = await $.ajax({
                    method: "POST",
                    url: "https://" + this.#hostName + "/iam",
                    data: JSON.stringify({ refreshToken: decodeURIComponent(cachedRefreshToken) }),
                    dataType: "json",
                });

                return this.#handleIamResponse(response);
            }
            catch (e) {
                // Do nothing, the refresh token has expired.
            }
        }
        
        const response = await new Promise((resolve, reject) => {
            $.ajax({
                method: "POST",
                url: "https://" + this.#hostName + "/iam",
                data: JSON.stringify({ username: "guest", password: "guest" }),
                dataType: "json",
                success: resolve,
                error: reject
            });
        });

        return this.#handleIamResponse(response);
    }

    #handleIamResponse(response) {
        const expiration = new Date();
        expiration.setTime(expiration.getTime() + (response.validity * 1000));
        document.cookie = "accessToken=" + JSON.stringify(response) + "; expires=" + expiration.toUTCString() + "; path=/";
        document.cookie = "refreshToken=" + response.refreshToken + "; path=/";

        return response.accessToken;
    }
}