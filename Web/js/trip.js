async function init(tripName, isLoggedIn) {
    const trip = await getTrip(tripName);
    const places = await getPlacesForTrip(tripName);
    
    // Title.
    document.title = getDocumentTitle(trip);
    $('#name').html(getTitle(trip));
    
    // Map.
    initializeMapForTrip("map", trip, places);
    
    // Dates.
    $('#dates').html(getDatesComponent(trip));

    // Stays.
    $('#hotels').html(getStaysComponent(trip));

    // Flights.
    $('#flights').html(getFlightsComponent(trip));
    
    // Notes.
    $('#notes').html(getNotesComponent(trip, isLoggedIn));
    
    // Public holidays.
    $('#holidays').html(getPublicHolidaysComponent(trip, isLoggedIn));

    // Calendar.
    $('#calendar').html(getCalendarComponentForTrip(trip, places, isLoggedIn));

    // Expensify.
    $('#expensify').html(await getExpensifyComponentForTrip(trip, isLoggedIn));

    // Albums.
    $('#albums').html(getAlbumsComponentForTrip(trip, places, isLoggedIn)); 

    // Stats.
    if (!isDayTrips(trip) && trip.start < now) {
        $('#stats').html(getStatsComponent(trip.stats));
    }

    // Utilities.
    $('#utilities').html(getFooter(isLoggedIn, getAdditionalFooterLinks(trip, places)));

    // Timezone.
    $('#timezone').html(getTimezoneComponent());
}

function getDocumentTitle(trip) {
    return trip.countries.map(country => configuration.countries[country].emoji).join("") + " " + trip.name + " " + trip.year;
}

function getTitle(trip) {
    return trip.countries.map(getFlagImage).join(" ") + " " + trip.name + " " + trip.year
}

function getAdditionalFooterLinks(trip, places) {
    if (isDayTrips(trip)) {
        return [ "<span id=\"progressBar\"/></span>" ];
    }

    const links = [];

    const date = new Date(trip.start * 1000);
    links.push("<a href=\"https://calendar.google.com/calendar/u/1/r/week/" + date.getFullYear() + "/" + (date.getMonth() + 1) + "/" + date.getDate() + "\">Zobrazit v kalendáři</a>");

    links.push("<a onclick=\"changeName(" + trip.id + ")\">Přejmenovat</a>");

    if (trip.end < now) {
        return links;
    }
    
    links.push("<a onclick=\"moveTrip(" + trip.id + ", " + trip.start + ")\">Přesunout</a>");

    links.push("<a onclick=\"archiveTrip(" + trip.id + ")\">Archivovat</a>");

    links.push("<a onclick=\"loadTrip('" + trip.id + "')\">Načíst</a>");

    const aiMapPointsArg = serializeForHtmlAttribute(places.map(place => { return { id: place.id, name: escapeStringForHtml(place.name), country: escapeStringForHtml(place.country) }; }));
    links.push("<a onclick=\"getAiMapPoints(" + aiMapPointsArg + ")\">Vygenerovat mapu</a>");

    links.push("<a onclick=\"addUsefulLink('" + trip.id + "')\">Přidat odkaz</a>");
    links.push("<a onclick=\"addNote('" + trip.id + "')\">Přidat poznámku</a>");

    links.push("<span id=\"progressBar\"/></span>");

    return links;
}

function getDatesComponent(trip) {
    return getListComponent("Termín", [ getFromDateToDateString(trip.start, trip.end, true, true) ]);
}

function getStaysComponent(trip) {
    if (trip.stays.length === 0) {
        return "";
    }
    
    return getListComponent("Ubytování", trip.stays.map(stay => 
            "<a href=\"https://www.google.com/maps/search/" + stay.address + "\">" + stay.name + "</a> (" + getFromDateToDateString(stay.start, stay.end, true, false) + ")"));
}

function getFlightsComponent(trip) {
    if (trip.flights.length === 0) {
        return "";
    }

    return getListComponent("Lety", trip.flights.map(flight => {
        return getAirportLink(flight.from) + " - " + getAirportLink(flight.to) + " (" + getFlightLink(flight.flight) + " " + getDateString(flight.start) + " " + getTimeString(flight.start) + ")"
    }));
}

async function getAiMapPoints(places) {
    const placesWithPointsCount = places.map(place => {
        const pointsCount = prompt("Zadej počet bodů pro " + place.name + ", " + place.country + ":");
    
        if (pointsCount == null || isNaN(pointsCount) || Number(pointsCount) <= 0) {
            return undefined;
        }

        
        return { id: place.id, pointsCount: pointsCount };
    }).filter(place => place !== undefined);
    
    const points = [];

    initProgressBar(placesWithPointsCount.length);
    for (let i = 0; i < placesWithPointsCount.length; ++i) {        
        (await getSuggestedMapPoints(placesWithPointsCount[i].id, placesWithPointsCount[i].pointsCount)).forEach(point => points.push(point));
        updateProgressBar(i + 1, placesWithPointsCount.length);
    }

    const result = "Name,Latitude,Longitude\n" 
        + points.filter(point => point.latitude != "UNKNOWN" && point.longitude != "UNKNOWN").map(point => point.name + "," + point.latitude + "," + point.longitude).join("\n");

    console.log(result);
    await navigator.clipboard.writeText(result);

    alertConfirmation();
}

async function archiveTrip(tripId) {
    if (!confirm("Jsi si jist, že chceš archivovat tento výlet?")) {
        return;
    }

    executeAndAlertConfirmation("ArchiveTrip", { tripId: tripId });
}

async function moveTrip(tripId, oldStart) {
    const days = prompt("Zadej počet dnů, o kolik se má výlet přesunout (může být záporné):");
    if (days == null || days == "" || isNaN(days)) {
        return;
    }

    executeAndAlertConfirmation("MoveTrip", { tripId: tripId, start: oldStart + days * 86400 });
}

async function loadTrip(id) {
    const trip = prompt("Zadej název výletu k nahrání:");
    if (trip == null || trip == "") {
        return;
    }

    const candidateTripIdentifier = await getResponse("GetTripIdentifier", { name: trip });
    executeAndAlertConfirmation("LoadTrip", { tripId: id, candidateTripId: candidateTripIdentifier.id });
}

async function changeName(tripId) {
    const name = prompt("Zadej nové jméno výletu:");
    if (name == null || name == "") {
        return;
    }

    executeAndAlertConfirmation("ChangeTripIdentifier", { tripId: tripId, name: name });
}