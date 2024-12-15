async function init(tripId, isLoggedIn) {
    const trip = await api.getTrip(tripId);
    const places = await api.listRegularPlaces(tripId);
    
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

async function archiveTrip(tripId) {
    if (!confirm("Jsi si jist, že chceš archivovat tento výlet?")) {
        return;
    }

    api.removeTrip(tripId).then(alertConfirmation);
}

async function moveTrip(tripId, oldStart) {
    const days = prompt("Zadej počet dnů, o kolik se má výlet přesunout (může být záporné):");
    if (days == null || days == "" || isNaN(days)) {
        return;
    }

    api.updateTripStart(tripId, oldStart + days * 86400).then(alertConfirmation);
}

async function loadTrip(oldId) {
    const newId = prompt("Zadej identifikátor výletu k nahrání:");
    if (newId == null || newId == "") {
        return;
    }

    api.replaceTrip(oldId, newId).then(alertConfirmation);
}

async function changeName(tripId) {
    const name = prompt("Zadej nové jméno výletu:");
    if (name == null || name == "") {
        return;
    }

    api.updateTripName(tripId, name).then(alertConfirmation);
}