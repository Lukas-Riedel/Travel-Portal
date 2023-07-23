
async function init(isLoggedIn) {
    const places = await getPlaces(!isLoggedIn);
    const trips = await getTrips();

    // Title.
    $('#title').html(getTitle(places));

    // Main menu.
    $('#mainMenu').html(getMainMenu());

    // Map.
    initializeMap("map", places);

    // Content.
    $('#main').html(await getContentComponent(trips, isLoggedIn || Cookies.get(configuration.cookies["DisplayFutureTrips"])));

    // Footer.
    $('#footer').html(getFooter(isLoggedIn));
}

function getTitle(places) {
    return "<img src=\"img/icon.jpg\"><br>" + getCountries(places).map(getFlagImage).join(" ");
}

async function getContentComponent(trips, displayFutureTrips) {
    const isFutureTrip = trip => trip.end > now && !isDayTrips(trip);
    const isPastTrip = trip => trip.end < now && !isDayTrips(trip);

    return [
        await getFeaturedTrip(getFirstElement(trips.filter(isFutureTrip)), displayFutureTrips),
        getFutureTrips(trips.filter(isFutureTrip), displayFutureTrips),
        getPastTripsAndDayTrips(reversed(trips.filter(isPastTrip)).concat(trips.filter(isDayTrips)))
    ].join("");
}

async function getFeaturedTrip(trip, displayFutureTrips) {
    if (trip === undefined || !(displayFutureTrips || trip.start < now || Cookies.get(configuration.cookies["DisplayFeaturedTrip"]))) {
        return "";
    }

    return await doGetFeaturedTrip(await getTrip(getFullyQualifiedTripName(trip)));
}

function getFutureTrips(trips, displayFutureTrips) {
    if (!displayFutureTrips) {
        return "";
    }

    const headerRowColumns = [
        { hideifSimplified: false, content: "Název"},
        { hideifSimplified: false, content: "Termín"},
        { hideifSimplified: false, content: "Rok"},
        { hideifSimplified: true,  content: "Dnů"},
        { hideifSimplified: true,  content: "Pracovních dnů"},
        { hideifSimplified: false,  content: "Dovolená"},
        { hideifSimplified: true, content: "Výdaje"}
    ];

    const contentRowColumnsSelector = trip => [
        { hideifSimplified: false, content: "<a href=\"https://" + configuration.hostName + "/trip/" + getFullyQualifiedTripName(trip) + "\"><strong style=\"color: black\">" + trip.name + "</strong></a>" },
        { hideifSimplified: false, content: getFromDateToDateString(trip.start, trip.end, true, false) },
        { hideifSimplified: false, content: "<a style=\"color: black\" href=\"https://" + configuration.hostName + "/year/" + trip.year + "\">" + trip.year + "</a>" },
        { hideifSimplified: true,  content: Math.ceil(trip.days.total) },
        { hideifSimplified: true,  content: Math.ceil(trip.days.working) },
        { hideifSimplified: false,  content: trip.start < now ? "N/A" : formatVacation(trip.vacation) },
        { hideifSimplified: true, content: getFormattedCost(trip) }
    ];

    return getGeneralTable(headerRowColumns, contentRowColumnsSelector, trips);
}

function getPastTripsAndDayTrips(trips) {
    return [...new Set(trips.map(trip => trip.year))].map(year => "<div class=\"year\"><h3><a href=\"https://" + configuration.hostName + "/year/" + year + "\">" + year + "</a></h3>" 
        + getAlbumsComponentForTrips(trips.filter(trip => trip.year === year)) + "</div>").join("<br>");
}