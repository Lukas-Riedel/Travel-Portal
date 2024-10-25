const problemNames = {"FUTURE_COUNTRIES_WITHOUT_PUBLIC_HOLIDAYS_CALENDAR":"Státy bez specifikovaného kalendáře státních svátků","ALBUMS_WITHOUT_PLACE_LINK":"Alba bez asociace s místem","UNNAMED_AIRLINES":"Nepojmenované aerolinky","COUNTRIES_WITHOUT_GEOGRAPHICAL_REGIONS":"Státy bez geografického členění","PLACES_WITHOUT_ADMINISTRATIVE_CATEGORY":"Místa bez kategorie specifické pro stát","PLACES_WITHOUT_TIME":"Místa bez specifikovaného času","LOGGED_FLIGHTS_WITHOUT_FLIGHT_EVENT":"Zalogované lety bez události v kalendáři","PLACE_EVENT_WITH_INCORRECT_TIME":"Události v kalendáři, které nezačínají v násobku půlhodiny","PLACE_EVENT_WITH_INCORRECT_DURATION":"Doby trvání událostí v kalendáři, které nejsou násobkem půlhodiny","DUPLICATED_PLACE_IDENTIFIERS":"Zduplikované identifikátory míst","NON_LOGGED_FLIGHTS":"Nezalogované lety","GEOGRAPHICAL_REGIONS_WITH_SAME_NAME":"Geografické regiony se stejným jménem","LOW_QUALITY_PHOTOS_WITHOUT_REPLACEMENT":"Nekvalitní fotky bez náhrady","LOGGED_ERRORS":"Hlášené chyby"};

async function init() {
    const isNextTrip = trip => trip.end > now && !isDayTrips(trip);

    const tripId = getFirstElement((await api.listTrips()).filter(isNextTrip)).id;
    const trip = await api.getTrip(tripId);

    if (trip !== undefined) {
        // Navigation.
        $('#navigation').html(getNavigationComponent(trip));
    
        // Trip.
        $('#trip').html(await doGetFeaturedTrip(trip));
    
        // Expensify.
        $('#expensify').html(await getExpensifyComponentForAcp(trip));
    }
    
    // Utilities.
    $('#utils').html(getUtilitiesComponent(trip));

    // Problems.
    $('#problems').html(await getProblemsComponent());
        
    // Flights.
    $('#flights').html(await getFlightsComponent());

    // Configuration.
    $('#configuration').html(await getConfigurationComponent());

    // Footer.
    $('#footer').html(getFooter(true));
}

function getNavigationComponent(trip) {
    const headerRowColumns = [
        { hideifSimplified: false, content: "Hotel" },
        { hideifSimplified: false, content: "Let" },
        { hideifSimplified: false, content: "Odkazy" }
    ];

    const contentRowColumnsSelector = trip => {
        const contentRowColumns = [];

        let hotel = reversed(trip.stays).find(stay => stay.start < now);
        if (hotel === undefined) {
            hotel = getFirstElement(trip.stays);
        }

        if (hotel !== undefined) {
            contentRowColumns.push({ hideifSimplified: false, content: "<a href=\"https://www.google.com/maps/search/" + hotel.address + "\">" + hotel.name + "</a>"});
        }
        else {
            contentRowColumns.push({ hideifSimplified: false, content: "<img src=\"img/x.png\">" })
        }

        const flight = getFirstElement(trip.flights.filter(flight => now < flight.end));

        if (flight !== undefined) {
            contentRowColumns.push({ hideifSimplified: false, content: "<a href=\"https://www.flightradar24.com/data/flights/" + flight.flight + "\">" + flight.flight + "</a>"});
        }
        else {
            contentRowColumns.push({ hideifSimplified: false, content: "<img src=\"img/x.png\">" })
        }

        if (trip.notes.length > 0) {
            contentRowColumns.push({ hideifSimplified: false, content: trip.notes.map(note => formatNote(note, trip.id, true)).join("<br>") });
        }
        else {
            contentRowColumns.push({ hideifSimplified: false, content: "<img src=\"img/x.png\">" })
        }

        return contentRowColumns;
    };

    return getGeneralTable(headerRowColumns, contentRowColumnsSelector, [ trip ]);
}

async function getProblemsComponent() {    
    const problems = await api.listProblems();

    const headerRowColumns = [
        { hideifSimplified: false, content: "Typ problému" },
        { hideifSimplified: false, content: "Zjištěné problémy" }
    ];

    const contentRowColumnsSelector = problem => {
        return [
            { hideifSimplified: false, content: problemNames[problem.name] },
            { hideifSimplified: false, content: problem.values.length === 1 ? getProblemValueName(problem.name, problem.values[0]) : ("<ol>" + problem.values.map(p => "<li>" + getProblemValueName(problem.name, p) + "</li>").join("") + "</ol>") }
        ];
    };

    return getGeneralTable(headerRowColumns, contentRowColumnsSelector, problems);
}

function getProblemValueName(problemName, problemValue) {
    const resolver = getProblemResolver(problemName, problemValue.context);
    return problemValue.name + (resolver !== undefined ? (" <a style=\"color: green\" onclick='" + resolver + "'>Vyřešit</a>") : "");
}

function getProblemResolver(problemName, context) {
    if (problemName == "PLACES_WITHOUT_ADMINISTRATIVE_CATEGORY") {
        return "addGeoRegionExtensionForPlace(" + context.placeId + ")";
    }
    if (problemName == "COUNTRIES_WITHOUT_GEOGRAPHICAL_REGIONS") {
        return "addGeoRegion(\"" + context.country + "\")";
    }
    if (problemName == "NON_LOGGED_FLIGHTS") {
        return "logFlight(" + context.scheduledDeparture + ", \"" + context.flight + "\", \"" + context.from + "\", \"" + context.to + "\", " + context.tripId + ")";
    }
    if (problemName == "FUTURE_COUNTRIES_WITHOUT_PUBLIC_HOLIDAYS_CALENDAR") {
        const idSuffix = ("COUNTRIES" + context.country).replace(/\s/g, "");
        return "changeConfigurationValue(\"" + idSuffix + "\", \"COUNTRIES\", \"" + context.country + "\")";
    }
    if (problemName == "UNNAMED_AIRLINES") {
        const idSuffix = ("AIRLINES" + context.code).replace(/\s/g, "");
        return "changeConfigurationValue(\"" + idSuffix + "\", \"AIRLINES\", \"" + context.code + "\")";
    }
    if (problemName == "DUPLICATED_PLACE_IDENTIFIERS") {
        return "resolveDuplicatedPlaceIdentifiers(" + JSON.stringify(context.places) + ")";
    }
    return undefined;
}

async function getFlightsComponent() {    
    const flights = await getFutureFlights();

    const headerRowColumns = [
        { hideifSimplified: true, content: "Let" },
        { hideifSimplified: false, content: "Z" },
        { hideifSimplified: false, content: "Do" },
        { hideifSimplified: false, content: "Odlet" },
        { hideifSimplified: true, content: "Přílet" },
        { hideifSimplified: false, content: "Cena" }
    ];

    const contentRowColumnsSelector = flight => {
        return [
            { hideifSimplified: true, content: getFlightLink(flight.flight) },
            { hideifSimplified: false, content: getAirportLink(flight.from) },
            { hideifSimplified: false, content: getAirportLink(flight.to) },
            { hideifSimplified: false, content: getDateString(flight.start) + " " + getTimeString(flight.start) },
            { hideifSimplified: true, content: getDateString(flight.end) + " " + getTimeString(flight.end) },
            { hideifSimplified: false, content: getCheckFlightPriceLink(flight) }
        ];
    };

    return getGeneralTable(headerRowColumns, contentRowColumnsSelector, flights);
}

function getCheckFlightPriceLink(flight) {
    const airlineCode = flight.flight.substring(0, 2);
    const airlinePart = (airlineCode in configuration.airlines) ? (" airline " + configuration.airlines[airlineCode]) : airlineCode;
    return "<a href=\"https://www.google.com/travel/flights?q=One way flight from " + flight.from.name + " to " + flight.to.name + " on " + getDateString(flight.start, true) + airlinePart + "\">Zkontrolovat</a>";
}

function getUtilitiesComponent(trip) {
    const previousFlight = getLastElement(trip.flights.filter(flight => now > flight.start));

    // Links.
    const links = [
        { name: "Zobrazit plán", link: "/plan" },
        { name: "Zobrazit plán výletů", link: "/plan/trip" },
        { name: "Zobrazit sledování času", link: "/tracking" },
        { name: "Zobrazit statistiky", link: "/stats" },
        { name: "Autorizovat vůči Google", link: "https://accounts.google.com/o/oauth2/v2/auth?client_id=" + configuration.googleApiCredentials.clientId + "&prompt=consent&redirect_uri=https://" + configuration.hostName + "&response_type=code&access_type=offline&scope=" + googleApiAuthorizationScopes.join(" ") }
    ];
    Object.keys(configuration.cookies).forEach(cookieName => links.push({ name: "Nastavit " + cookieName + " cookie", link: "https://" + configuration.hostName + "/login.php?cookies=" + configuration.cookies[cookieName] }));

    // Tools.
    const tools = [
        { name: "Aktualizovat alba", action: "runJob('UpdateAlbum', {})" },
        { name: "Aktualizovat kalendář", action: "runJob('UpdateCalendar', { watchId: configuration.googleCalendarApi.watchId })" },
        { name: "Získat GeoJSON s geografickými regiony", action: "getGeoJson()" },
        { name: "Přidat předplatné", action: "addSubscription()" },
        { name: "Přidat geografický region", action: "addGeoRegion()" },
        { name: "Přidat složený region", action: "addCompositeRegion()" },
        { name: "Přidat trvalé místo", action: "addPermanentPlace()" },
        { name: "Přidat plánované místo", action: "addPlaceCandidate()" }
    ];

    // Combine all into a single table.
    const utils = [];
    for (let i = 0; i < Math.max(links.length, tools.length); ++i) {
        const entry = {};

        if (i < links.length) {
            entry["link"] = links[i];
        }

        if (i < tools.length) {
            entry["tool"] = tools[i];
        }

        utils.push(entry);
    }

    const headerRowColumns = [
        { hideifSimplified: false, content: "Odkazy" },
        { hideifSimplified: false, content: "Nástroje" }
    ];

    const contentRowColumnsSelector = entry => [
        { hideifSimplified: false, content: entry.link === undefined ? "" : "<a href=\"" + entry.link.link + "\">" + entry.link.name + "</a>" },
        { hideifSimplified: false, content: entry.tool === undefined ? "" : "<a onclick=\"" + entry.tool.action + "\">" + entry.tool.name + "</a>" }
    ];

    return getGeneralTable(headerRowColumns, contentRowColumnsSelector, utils);
}

async function logFlight(start, flight, from, to, tripId) {
    // Switch to manual logging in new UI.
    if (confirm("Přeješ si zalogovat let " + flight + "?")) {
        const loggedFlight = await api.logTripFlight(tripId, flight, from, to, start);
        alert("Let: " + loggedFlight.flight + " (" + loggedFlight.from.name + " - " + loggedFlight.to.name + ")"
            + "\nLetadlo: " + loggedFlight.aircraft + " (" + loggedFlight.registration + ")"
            + "\nOdlet: " + getDateString(loggedFlight.start) + " " + getTimeString(new Date(new Date(loggedFlight.start * 1000).toLocaleString('en-US', { timeZone: loggedFlight.from.timezone })))
            + "\nPřílet: " + getDateString(loggedFlight.end) + " " + getTimeString(new Date(new Date(loggedFlight.end * 1000).toLocaleString('en-US', { timeZone: loggedFlight.to.timezone }))));
    }
}

async function getConfigurationComponent() {
    const headerRowColumns = [
        { hideifSimplified: false, content: "Typ" },
        { hideifSimplified: false, content: "Klíč" },
        { hideifSimplified: true, content: "Hodnota" },
        { hideifSimplified: false, content: "" }
    ];

    const contentRowColumnsSelector = entry => {
        const idSuffix = (entry.type + entry.key).replace(/\s/g, "");

        const buttons = [
            { 
                action: "changeConfigurationValue('" + idSuffix + "', '" + entry.type + "', " + (entry.key == null ? "null" : ("'" + entry.key + "'")) + ")",
                image: "img/edit.png"
            }
        ];

        return [
            { hideifSimplified: false, content: entry.type },
            { hideifSimplified: false, content: entry.key == null ? "" : entry.key },
            { hideifSimplified: true, content: "<div style=\"word-break: break-all; max-width: 300px;\" id=\"configurationValue" + idSuffix + "\">" + entry.value + "</div>" },
            { hideifSimplified: false, content: "<div class=\"utilitiesColumn\">" + buttons.map(button => "<a onclick=\"" + button.action + "\"><img src=\"" + button.image + "\"></a>").join("") + "</div>" }
        ];
    };

    const modifiableConfiguration = await api.listConfigurationEntries("modifiable");
    return getGeneralTable(headerRowColumns, contentRowColumnsSelector, Object.keys(modifiableConfiguration).flatMap(type => createConfigurationEntries(mapConfigurationEntryType(type), modifiableConfiguration[type])));
}

function createConfigurationEntries(type, obj) {
    return typeof(obj) === "object" ? Object.keys(obj).map(key => { return { type: type, key: key, value: stringifyConfigurationEntryValue(obj[key]) }; }) : [ { type: type, key: null, value: obj } ];
}

function mapConfigurationEntryType(type) {
    return type.split(/(?=[A-Z])/).map(x => x.toUpperCase()).join("_");
}

function stringifyConfigurationEntryValue(value) {
    return typeof(value) === "object" ? JSON.stringify(value) : value;
}

function changeConfigurationValue(idSuffix, type, key) {
    const newValue = prompt("Zadej novou hodnotu:", document.getElementById("configurationValue" + idSuffix).innerText);

    if (newValue == null || newValue == "") {
        return;
    }

    api.updateConfigurationEntry(type, key, newValue).then(reload);
}

function addSubscription() {
    const description = prompt("Zadej název předplatného:");
    if (description == null || description == "") {
        return;
    }

    const currency = prompt("Zadej měnu předplatného:");
    if (currency == null || currency.length != 3) {
        return;
    }

    const cost = prompt("Zadej hodnotu předplatného:");
    if (cost == null || isNaN(cost) || Number(cost) <= 0) {
        return;
    }

    const expirationString = prompt("Zadej expiraci předplatného (ve formátu DD.MM.YYYY):");
    if (expirationString == null || expirationString == "") {
        return;
    }

    const expirationTokens = expirationString.split(".");
    if (expirationTokens.length !== 3) {
        return;
    }

    const expirationTimestamp = new Date(expirationTokens[2], expirationTokens[1] - 1, expirationTokens[0]).getTime() / 1000;

    api.createSubscription(description, cost, currency, expirationTimestamp).then(reload);
}

function addGeoRegion(country = undefined) {
    const geoJson = prompt("Zadej GeoJSON reprezentaci regionu:");
    if (geoJson == null || geoJson == "") {
        return;
    }

    const features = [];
    const obj = JSON.parse(geoJson);
    if (obj.type === "FeatureCollection") {
        obj.features.forEach(feature => features.push(feature));
    }
    else if (obj.type === "Feature") {
        features.push(obj);
    }
    else if (obj.type === "GeometryCollection" && obj.geometries.length === 1) {
        features.push({
            "type": "Feature",
            "properties": {},
            "geometry": obj.geometries[0]
        });
    }

    features.forEach(feature => {
        const name = prompt("Zadej název následujícího regionu. Ponech prázdné, pokud se region nemá přidávat:\n\n" 
            + Object.keys(feature.properties).map(property => property + " - " + feature.properties[property]).join("\n"), 
            Object.values(feature.properties).join(", "));
        if (name == null || name == "") {
            return;
        }

        country = prompt("Je region " + name + " specifický pro stát? Pokud ano, zadej jméno státu. Jinak ponech prázdné:", country === undefined ? "" : country);
        if (country == null || country == "") {
            country = undefined;
        }

        let radius = Number.parseInt(prompt("Má region " + name + " nějaký rádius kolem svých hranic (např. pokud se jedná o pobřeží)? Pokud ano, zadej rádius v kilometrech. Jinak zadej nulu:", 0));
        if (radius == null || radius == "" || Number.isNaN(radius) || radius < 0) {
            radius = 0;
        }

        const regionGeoJson = JSON.stringify({
            type: "Feature",
            geometry: feature.geometry
        });

        let category = undefined;
        if (country !== undefined) {
            category = "ADMINISTRATIVE";
        }
        else {
            category = prompt("Zadej kategorii kategorie:\n\nMožné hodnoty:\n" + [ 'CONTINENT','COUNTRY','ADMINISTRATIVE','OCEAN','SEA','BAY','VARIABLE','ISLAND','REGION' ].join("\n"));
            if (category == null || category == "") {
                return;
            }
        }

        api.createGeographicalCategory(name, country, category, radius, regionGeoJson).then(alertConfirmation);
    });
}

async function addGeoRegionExtensionForPlace(placeId) {    
    const regionName = prompt("Zadej název regionu:");
    if (regionName == null || regionName == "") {
        return;
    }

    const place = await api.getPlace(placeId);

    let category = undefined;
    let regionCountry = prompt("Je region " + regionName + " specifický pro stát? Pokud ano, zadej jméno státu. Jinak ponech prázdné:", place.country);
    if (regionCountry == null || regionCountry == "") {
        regionCountry = undefined;
        category = prompt("Zadej kategorii kategorie:\n\nMožné hodnoty:\n" + [ 'CONTINENT','COUNTRY','ADMINISTRATIVE','OCEAN','SEA','BAY','VARIABLE','ISLAND','REGION' ].join("\n"));
        if (category == null || category == "") {
            return;
        }
    }
    else {
        regionCountry = resolveCountry(regionCountry);
        category = "ADMINISTRATIVE"
    }

    api.createGeographicalExtensionCategory(regionName, regionCountry, category, place.latitude, place.longitude).then(alertConfirmation);
}

function addCompositeRegion() {
    alert("Tato metoda pravděpodobně nebude fungovat, protože se pole nedokáže zakódovat do URL string. Použij raději API.");

    const name = prompt("Zadej název regionu:");
    if (name == null || name == "") {
        return;
    }

    const included = prompt("Zadej názvy zahrnutých regionů (oddělených čárkou):");
    if (included == null || included == "") {
        return;
    }

    let excluded = prompt("Zadej názvy vyloučených regionů (oddělených čárkou):");
    if (excluded == null) {
        excluded = "";
    }

    const category = prompt("Zadej kategorii kategorie:\n\nMožné hodnoty:\n" + [ 'CONTINENT','COUNTRY','ADMINISTRATIVE','OCEAN','SEA','BAY','VARIABLE','ISLAND','REGION' ].join("\n"));
    if (category == null || category == "") {
        return;
    }

    api.createCompositeCategory(name, category, included.split(","), excluded.split(",")).then(alertConfirmation);
}

async function addPermanentPlace() {
    const name = prompt("Zadej název trvalého místa:");
    if (name == null || name == "") {
        return;
    }
    
    const address = prompt("Zadej adresu trvalého místa:", name);
    if (address == null || address == "") {
        return;
    }

    const resolvedAddress = await api.getCoordinates(address);
    if (confirm("Nalezené místo je ve státě " + resolvedAddress.country + " (" + resolvedAddress.latitude + ", " + resolvedAddress.longitude + "). Přeješ si toto místo přidat?")) {
        api.createPermanentPlace(name, address).then(alertConfirmation);
    }
}

async function getGeoJson() {
    await navigator.clipboard.writeText(JSON.stringify(await api.runJob("GetGeographicalRegions", {})));
    
    alertConfirmation();
}

function resolveDuplicatedPlaceIdentifiers(places) {
    const id = Number.parseInt(prompt("Zadej identifikátor místa k odstranění (pouze jedno):\n\n" + places.map(place => place.id + " - " + place.name).join("\n")));
    if (id == null || id == "" || Number.isNaN(id) || !places.some(place => place.id == id)) {
        return;
    }

    api.removeCandidatePlace(id).then(alertConfirmation);
}