async function init(isLoggedIn) {
    const flights = await getLoggedFlights();
    const airports = await getLoggedAirports(flights);

    // Title.
    $('#title').html(getTitle(airports));

    // Main menu.
    $('#mainMenu').html(getMainMenu());

    // Map.
    initializeMapWithFlightPaths("map", flights, airports);

    // Content.
    $('#main').html(getContentComponent(flights));

    // Footer.
    $('#footer').html(getFooter(isLoggedIn));
}

function getTitle(airports) {
    return "<img src=\"img/icon.jpg\"><br>" + getCountries(airports).map(getFlagImage).join(" ");
}

function getContentComponent(flights) {
    const headerRowColumns = [
        { hideifSimplified: false, content: "Datum" },
        { hideifSimplified: true, content: "Let" },
        { hideifSimplified: false, content: "Z" },
        { hideifSimplified: false,  content: "Do" },
        { hideifSimplified: true,  content: "Vzdálenost" },
        { hideifSimplified: false,  content: "Odlet" },
        { hideifSimplified: false, content: "Přílet" },
        { hideifSimplified: true, content: "Registrace" },
        { hideifSimplified: true, content: "Letadlo" },
        { hideifSimplified: true, content: "Provozovatel"}
    ];

    const contentRowColumnsSelector = flight => [
        { hideifSimplified: false, content: "<strong>" + getDateString(new Date(new Date(flight.start * 1000).toLocaleString('en-US', { timeZone: flight.from.timezone })), true) + "</strong>" },
        { hideifSimplified: true,  content: "<a href=\"https://www.flightradar24.com/data/flights/" + flight.flight + "\">" + flight.flight },
        { hideifSimplified: false, content: "<a href=\"https://www.google.com/maps/search/Letiště " + (flight.from.name == null ? flight.from.name : (flight.from.name + " (" + flight.from.code + ")")) + "\">" + (flight.from.name == null ? flight.from.name : (flight.from.name + " (" + flight.from.code + ")")) + "</a>" },
        { hideifSimplified: false, content: "<a href=\"https://www.google.com/maps/search/Letiště " + (flight.to.name == null ? flight.to.name : (flight.to.name + " (" + flight.to.code + ")")) + "\">" + (flight.from.name == null ? flight.to.name : (flight.to.name + " (" + flight.to.code + ")")) + "</a>" },
        { hideifSimplified: true,  content: formatKilometersCount(flight.distance.toFixed(0)) },
        { hideifSimplified: false, content: getTimeString(new Date(new Date(flight.start * 1000).toLocaleString('en-US', { timeZone: flight.from.timezone }))) },
        { hideifSimplified: false, content: getTimeString(new Date(new Date(flight.end * 1000).toLocaleString('en-US', { timeZone: flight.to.timezone }))) },
        { hideifSimplified: true,  content: "<a href=\"https://www.flightradar24.com/data/aircraft/" + flight.registration + "\">" + flight.registration },
        { hideifSimplified: true,  content: flight.aircraft },
        { hideifSimplified: true,  content: resolveAirline(flight.flight) }
    ];

    return getGeneralTable(headerRowColumns, contentRowColumnsSelector, flights);
}

function resolveAirline(flightNumber) {
    return flightNumber.substring(0, 2);
}