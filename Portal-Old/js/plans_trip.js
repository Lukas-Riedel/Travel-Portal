async function init(tripId, isLoggedIn) {
    const trip = await api.getTrip(tripId);
    const places = await api.listCandidatePlaces(trip.id, "DATES");
    
    // Title.
    document.title = getDocumentTitle(trip);
    $('#name').html(getTitle(trip));
    
    // Map.
    initializeMap("map", places);

    // Calendar.
    $('#calendar').html(getCalendarComponentForTripCandidate(places));
    
    // Notes.
    $('#notes').html(getNotesComponent(trip, isLoggedIn));
    
    // Public holidays.
    //$('#holidays').html(getPublicHolidaysComponent(trip, isLoggedIn));

    // Utilities.
    $('#utilities').html(getFooter(isLoggedIn, getAdditionalFooterLinks(trip)));

    // Timezone.
    $('#timezone').html(getTimezoneComponent());
}

function getDocumentTitle(trip) {
    return trip.countries.map(country => configuration.countries[country].emoji).join("") + " " + trip.name;
}

function getTitle(trip) {
    return trip.countries.map(getFlagImage).join(" ") + " " + trip.name;
}

function getAdditionalFooterLinks(trip) {
    const links = [];
    links.push("<a onclick=\"addUsefulLink('" + trip.id + "')\">Přidat odkaz</a>");
    links.push("<a onclick=\"addNote('" + trip.id + "')\">Přidat poznámku</a>");
    return links;
}