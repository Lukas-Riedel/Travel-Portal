async function init(year, isLoggedIn) {  
    const trips = await api.listTrips(year, "EXPENSES");
    const places = await api.listRegularPlaces(undefined, undefined, undefined, year, undefined, isLoggedIn ? Number.MAX_SAFE_INTEGER : Math.round(now), "DATES");
    const statistics = (await api.getYear(year)).statistics;

    // Title.        
    document.title = getDocumentTitle(year);
    $('#title').html(getTitle(year, places));

    // Map.
    initializeMap("map", places);
    
    // Content.
    $('#main').html(getContentComponent(trips, isLoggedIn || Cookies.get(configuration.cookies["DisplayFutureTrips"])));

    // Expensify.
    $('#expensify').html(await getExpensifyComponentForYear(trips));

    // Albums.
    $('#albums').html(getAlbumsComponentForYear(places, isLoggedIn));

    // Stats.
    if (trips.some(trip => trip.end < now)) {
        $('#statistics').html(getStatsComponent(statistics));
    }

    // Footer.
    $('#footer').html(getFooter(isLoggedIn));
}

function getDocumentTitle(year) {
    return year;
}

function getTitle(year, places) {
    return year + "<br>" + getCountries(places).map(getFlagImage).join(" ");
}

function getContentComponent(trips, displayFutureTrips) {
    trips = trips.filter(trip => trip.start < now || displayFutureTrips);

    trips.sort((a, b) => isDayTrips(b) - isDayTrips(a));
    const closedYear = trips.every(trip => trip.end < now);

    const headerRowColumns = [
        { hideifSimplified: false, content: "Název"},
        { hideifSimplified: false, content: "Termín"},
        { hideifSimplified: true,  content: "Dnů"}
    ];

    if (!closedYear) {
        headerRowColumns.push(
            { hideifSimplified: true,  content: "Pracovních dnů"},
            { hideifSimplified: false,  content: "Dovolená"}
        );
    }

    headerRowColumns.push(
        { hideifSimplified: true, content: "Výdaje"}
    );

    const contentRowColumnsSelector = trip => {
        const result = [
            { hideifSimplified: false, content: "<a href=\"https://" + location.hostname + "/trip/" + trip.id + "\"><strong style=\"color: black\">" + trip.name + "</strong></a>" },
            { hideifSimplified: false, content: getFromDateToDateString(trip.start, trip.end, true, false) },
            { hideifSimplified: true,  content: Math.ceil(trip.days.total) }
        ];

        if (!closedYear) {
            if (isDayTrips(trip)) {
                result.push(
                    { hideifSimplified: true, content: "" },
                    { hideifSimplified: false, content: "" }
                );
            }
            else {
                result.push(
                    { hideifSimplified: true, content: Math.ceil(trip.days.working) },
                    { hideifSimplified: false, content: trip.start < now ? "N/A" : formatVacation(trip.vacation) }
                );
            }
        }

        if (isDayTrips(trip)) {
            result.push(
                { hideifSimplified: true, content: "" }
            );
        }
        else {
            result.push(
                { hideifSimplified: true, content: getFormattedCost(trip) }
            );
        }

        return result;
    };
    
    const additionalRowColumns = [
        { hideifSimplified: false, content: ""},
        { hideifSimplified: false, content: ""},
        { hideifSimplified: true,  content: "<strong>" + sum(trips.map(trip => trip.days.total)) + "</strong>"}
    ];

    if (!closedYear) {
        additionalRowColumns.push(
            { hideifSimplified: true,  content: "<strong>" + sum(trips.map(trip => trip.days.working)) + "</strong>"},
            { hideifSimplified: false,  content: "<strong>" + Math.round(sum(trips.filter(trip => trip.vacation !== null).map(trip => trip.vacation.expected))) + "</strong>"}
        );
    }

    additionalRowColumns.push(
        { hideifSimplified: true, content: "<strong>" + sum(trips.map(trip => trip.cost)).toFixed(0) + " " + configuration.mainCurrency + "</strong>"}
    );
    
    return getGeneralTable(headerRowColumns, contentRowColumnsSelector, trips, additionalRowColumns);
}