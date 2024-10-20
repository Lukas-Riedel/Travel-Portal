// High-level backend communication.
async function runJob(action, args) {
    return api.runJob(action, args).done(alertConfirmation);
}

async function getFutureFlights() {
    return (await api.listTrips(undefined, undefined, undefined, undefined, true)).flatMap(t => t.watchedFlights);
}

async function getPlaces(onlyPast) {
    return api.listRegularPlaces(undefined, undefined, undefined, undefined, onlyPast ? Math.round(now) : Number.MAX_SAFE_INTEGER);
}

async function getLoggedFlights() {
    return sorted((await api.listTrips(undefined, undefined, undefined, true)).flatMap(t => t.flights).filter(f => f.aircraft != null), (a, b) => b.start - a.start);
}

async function getLoggedAirports(loggedFlights = undefined) {
    loggedFlights = loggedFlights === undefined ? (await getLoggedFlights()) : [...loggedFlights];
    loggedFlights.sort((a, b) => a.start - b.start);

    const encounteredAirports = new Set();
    const result = [];

    loggedFlights.forEach(flight => {
        if (!encounteredAirports.has(flight.from.name)) {
            encounteredAirports.add(flight.from.name);
            result.push(flight.from);
        }
        if (!encounteredAirports.has(flight.to.name)) {
            encounteredAirports.add(flight.to.name);
            result.push(flight.to);
        }
    });
    
    return result;
}

// Common frontend.
function loadPage(initFunction) {
    $(document).ready(async () => {
        api = new Api("lriedel.cz"); // TODO
        configuration = await api.listConfigurationEntries("public");
        const albumsPerRow = $(window).width() / configuration.albumThumbnailImageSize.width;
        const newImageWidth = albumsPerRow - Math.floor(albumsPerRow) > 0.9 ? ($(window).width() / Math.ceil(albumsPerRow)) * 0.95 : configuration.albumThumbnailImageSize.width;
        const newImageHeight = newImageWidth / configuration.albumThumbnailImageSize.width * configuration.albumThumbnailImageSize.height;
        configuration.albumThumbnailImageSize.width = newImageWidth;
        configuration.albumThumbnailImageSize.height = newImageHeight;
        configuration.albumsPerRow = Math.floor($(window).width() / configuration.albumThumbnailImageSize.width);
        configuration.maximumCalendarEntriesPerRow = Math.floor($(window).width() / configuration.calendarEntryMinimumWidth);
        Object.keys(configuration.countries).forEach(country => configuration.countries[country].emoji = configuration.countries[country].unicode.split("-").map(c => "0x" + c).map(c => Number(c)).map(c => String.fromCodePoint(c)).join(""));
        const afterInitFunctionEvent = new Event("afterInitFunction");
        await initFunction();
        document.dispatchEvent(afterInitFunctionEvent);
    });    
}

function getPlacePrettyName(placeName) {
    const prettyNameEndIndex = placeName.indexOf("(");
    return prettyNameEndIndex === -1 ? placeName : placeName.substring(0, prettyNameEndIndex - 1);
}

function getCategoryPrettyName(placeName) {
    const prettyNameEndIndex = placeName.indexOf("(");
    return prettyNameEndIndex === -1 ? placeName : placeName.substring(0, prettyNameEndIndex - 1);
}

function alertConfirmation() {
    alert("OK");
}

function reload() {
    location.reload();
}

function getTimezoneComponent() {
    const lines = [ "Všechny časy jsou uvedeny pro časovou zónu " + Intl.DateTimeFormat().resolvedOptions().timeZone + "." ];
    if (forecastLastUpdateTime !== 0) {
        lines.push("Aktualizováno v " + getTimeString(new Date(forecastLastUpdateTime * 1000)) + ".");
    }
    return lines.join("<br><br>");
}

async function addUsefulLink(tripId) {
    const name = prompt("Zadej název odkazu:");
    if (name == null || name == "") {
        return;
    }
    
    const link = prompt("Zadej URL odkazu:");
    if (link == null || link == "") {
        return;
    }

    api.createTripNote(tripId, "<a href=\"" + link + "\">" + name + "</a>").done(reload);
}

function getPublicHolidaysComponent(trip, isLoggedIn) {
    if (!isLoggedIn || (trip.end != null && trip.end < now)) {
        return "";
    }

    const publicHolidays = trip.publicHolidays.map(holiday => holiday.date + " - " + holiday.name + " (" + holiday.country + ")");
    return publicHolidays.length === 0 ? "" : getListComponent("Státní svátky", publicHolidays);
}

function getNotesComponent(trip, isLoggedIn) {
    if (!isLoggedIn) {
        return "";
    }

    const notes = trip.notes.map(note => formatNote(note, trip.id, isLoggedIn));
    if (notes.length === 0) {
        return "";
    }

    return getListComponent("Poznámky", notes);
}

async function addNote(tripId) {
    const note = prompt("Zadej obsah poznámky:");
    if (note == null || note == "") {
        return;
    }

    api.createTripNote(tripId, note).done(reload);
}

function promptDate(name) {    
    const date = prompt("Zadej datum události " + name + " (ve formátu DD.MM.YYYY):");
    if (date == null || date == "") {
        return undefined;
    }

    const dateTokens = date.split(".");
    if (dateTokens.length != 3) {
        return undefined;
    }

    const year = dateTokens[2];
    const month = dateTokens[1] - 1;
    const day = dateTokens[0];

    if (month < 0 || month > 11) {
        return undefined;
    }

    if (day < 1 || day > 31) {
        return undefined;
    }

    return new Date(year, month, day).getTime() / 1000;
}

function promptTime(name, allowEmpty) {
    const time = prompt("Zadej začátek události " + name + " (ve formátu HH:MM)" + (allowEmpty ? ". Ponech prázdné, pokud se jedná o celodenní událost" : "") + ":");
    if (time == null) {
        return undefined;
    }
    if (time == "") {
        return allowEmpty ? 0 : undefined;
    }

    const timeTokens = time.split(":");
    if (timeTokens.length != 2) {
        return undefined;
    }

    const hours = timeTokens[0];
    const minutes = timeTokens[1];

    if (hours < 0 || hours > 23) {
        return undefined;
    }
    
    if (minutes < 0 || minutes > 60) {
        return undefined;
    }

    return Number(hours) * 3600 + Number(minutes) * 60;
}

async function removeCandidatePlace(placeId, name, country) {
    if (confirm("Jsi si jist, že chceš odstranit " + name + ", " + country + "?")) {
        api.removeCandidatePlace(placeId).done(reload);
    }
}

function getMainMenu() {
    const items = [
        "<a href=\"https://" + configuration.hostName + "/trip/\">Výlety</a>",
        "<a href=\"https://" + configuration.hostName + "/place/\">Místa</a>",
        "<a href=\"https://" + configuration.hostName + "/flight/\">Lety</a>"
    ];
    return "<table><tr>" + items.map(item => "<th>" + item + "</th>").join("") + "</tr></table>";   
}

function getFooter(isLoggedIn, additionalItems = []) {
    const utils = isLoggedIn ? [ "<a href=\"https://" + configuration.hostName + "/acp\">Admin Control Panel</a>" ].concat(additionalItems) : [ getLoginLink(isLoggedIn) ];
    return "<ul>" + getListItems(utils) + "</ul>";
}

async function addPlaceCandidate() {
    const name = prompt("Zadej název plánovaného místa:");
    if (name == null || name == "") {
        return;
    }
    
    const address = prompt("Zadej adresu plánovaného místa:", name);
    if (address == null || address == "") {
        return;
    }

    const resolvedAddress = await api.getCoordinates(address);
    if (confirm("Nalezené místo je ve státě " + resolvedAddress.country + " (" + resolvedAddress.latitude + ", " + resolvedAddress.longitude + "). Přeješ si toto místo přidat?")) {
        api.createCandidatePlace(name, address).done(alertConfirmation);
    }
}

async function doGetFeaturedTrip(trip) {
    const places = await api.listRegularPlaces(trip.id);
    const calendar = getCalendarDatesForTrip(trip, places, false).filter(date => date.date > now - 86400).slice(0, configuration.maximumNextTripCalendarEntries);

    const headerRowColumns = [
        { hideifSimplified: false, content: "Název"},
        { hideifSimplified: false, content: "Termín"}
    ];

    for (let i = 0; i < calendar.length; ++i) {
        headerRowColumns.push({ hideifSimplified: i !== 0, content: calendar[i].title });
    }

    const calendarRowColumns = [
        { hideifSimplified: false, rowspan: 2, content: "<h2 style=\"color: black\">" + getCountriesWithoutLayovers(trip, places).map(getFlagImage).join(" ") + " <a href=\"https://" + configuration.hostName + "/trip/" + trip.id + "\">" + trip.name + "</a></h2>" },
        { hideifSimplified: false, rowspan: 2, content: "<h2>" + getFromDateToDateString(trip.start, trip.end, true, false) + "</h2>" }
    ];

    for (let i = 0; i < calendar.length; ++i) {
        calendarRowColumns.push({ hideifSimplified: i !== 0, content: calendar[i].calendar });
    }

    const weatherRowColumns = [];

    for (let i = 0; i < calendar.length; ++i) {
        weatherRowColumns.push({ hideifSimplified: i !== 0, content: calendar[i].weather });
    }

    const headerRow = "<tr>" + headerRowColumns.map(column => getTableCell(column, true)).join("") + "</tr>";
    const calendarRow = "<tr>" + calendarRowColumns.map(column => getTableCell(column, false)).join("") + "</tr>";
    const weatherRow = "<tr>" + weatherRowColumns.map(column => getTableCell(column, false)).join("") + "</tr>";     
    
    return "<table id=\"nextTrip\">" + headerRow + calendarRow + weatherRow + "</table>";
}

function removeNote(id, tripId) {
    if (confirm("Opravdu chceš odstranit vybranou poznámku?")) {
        api.removeTripNote(tripId, id).done(reload);
    }
}

function initProgressBar(totalSteps) {
    updateProgressBar(0, totalSteps);
}

function updateProgressBar(step, totalSteps) {
    $('#progressBar').text(Math.round(100 * step / totalSteps) + "% (" + step + "/" + totalSteps + ")")
} 

function formatVacation(vacation) {
    if (vacation.maximum === 0) {
        return "N/A";
    }
    return "<span style=\"color: " + (vacation.maximum < vacation.expected ? "red" : "green") + "\">" + vacation.expected.toFixed(1) + " (" + vacation.maximum.toFixed(1) + ")</span>";
}

function formatKilometersCount(kilometers) {
    if (kilometers == 1) {
        return kilometers + " kilometr";
    }
    if (kilometers == 2 || kilometers == 3 || kilometers == 4) {
        return kilometers + " kilometry";
    }
    return kilometers + " kilometrů";
}

function formatDaysCount(days, round = 10) {
    days = Math.floor(days * round) / round;
    if (days == 1) {
        return days + " den";
    }
    if (days == 2 || days == 3 || days == 4) {
        return days + " dny";
    }
    return days + " dnů";
}

function formatStepsCount(steps) {
    if (steps == 1) {
        return steps + " krok";
    }
    if (steps == 2 || steps == 3 || steps == 4) {
        return steps + " kroky";
    }
    return steps + " kroků";
}

function formatVisitsCount(visits) {
    if (visits == 1) {
        return visits + " návštěva";
    }
    if (visits == 2 || visits == 3 || visits == 4) {
        return visits + " návštěvy";
    }
    return visits + " návštěv";
}

function formatMinutesCount(minutes) {
    if (minutes == 1) {
        return minutes + " minuta";
    }
    if (minutes == 2 || minutes == 3 || minutes == 4) {
        return minutes + " minuty";
    }
    return minutes + " minut";
}

function formatHoursCount(hours) {
    if (hours == 1) {
        return hours + " hodina";
    }
    if (hours == 2 || hours == 3 || hours == 4) {
        return hours + " hodiny";
    }
    return hours + " hodin";
}

function formatDuration(duration) {
    const hours = Math.floor(duration / 3600);
    const minutes = Math.floor((duration % 3600) / 60);
    return (hours == 0 ? "" : (formatHoursCount(hours) + " ")) + formatMinutesCount(minutes);
}

function formatAirportsCount(places) {
    if (places == 1) {
        return places + " letiště";
    }
    if (places == 2 || places == 3 || places == 4) {
        return places + " letiště";
    }
    return places + " letišť";
}

function formatPlacesCount(places) {
    if (places == 1) {
        return places + " místo";
    }
    if (places == 2 || places == 3 || places == 4) {
        return places + " místa";
    }
    return places + " míst";
}

function formatBeforeDaysTimestamp(timestamp) {
    const days = Math.floor((now - timestamp) / 86400);
    if (days == 0) {
        return "Dnes";
    }
    if (days == 1) {
        return "Včera";
    }
    return "Před " + days + " dny";
}

function formatCountriesCount(countries) {
    if (countries == 2 || countries == 1 || countries == 3 || countries == 4) {
        return countries + " země";
    }
    return countries + " zemí";
}

function formatPhotosCount(photos) {
    if (photos == 1) {
        return photos + " fotka";
    }
    if (photos == 2 || photos == 3 || photos == 4) {
        return photos + " fotky";
    }
    return photos + " fotek";
}

function formatNightsCount(nights) {
    if (nights == 1) {
        return nights + " noc";
    }
    if (nights == 2 || nights == 3 || nights == 4) {
        return nights + " noci";
    }
    return nights + " nocí";
}

function formatFlightsCount(flights) {
    if (flights == 1) {
        return flights + " let";
    }
    if (flights == 2 || flights == 3 || flights == 4) {
        return flights + " lety";
    }
    return flights + " letů";
}

function formatDifference(difference) {
    return "<span style=\"color: " + (difference <= 0 ? "green" : "red") + "\">" + difference + " %</span>";
}

function formatNote(note, tripId, showRemoveButton) {
    return note.content + (showRemoveButton ? " <a style=\"color: red;\" onclick=\"removeNote(" + note.id + ", " + tripId + ")\">Odstranit</a>" : "");
}

function escapeStringForHtml(unsafe) {
    return unsafe.replaceAll('"', '\"').replaceAll("'", '\\\'');
}

function serializeForHtmlAttribute(obj) {
    return JSON.stringify(obj).replaceAll('"', "'").replaceAll("\\\\", "\\");
}

function getFullyQualifiedTripName(trip) {
    return trip.name + " " + trip.year;
}

function decomposeFullyQualifiedTripName(tripName) {
    return {
        name: tripName.substring(0, tripName.length - 5),
        year: tripName.substring(tripName.length - 4, tripName.length)
    };
}

function getLoginLink(isLoggedIn) {
    return isLoggedIn ? "" : "<a href=\"https://" + configuration.hostName + "/login.php?origin=" + encodeURIComponent(window.location.href) + "\">Přihlášení</a>";
}

function toRangeString(obj) {
    return ((obj.min === obj.max) ? obj.min : (obj.min + " - " + obj.max));
}

function getListComponent(title, items) {
    return "<h4>" + title + "</h4><ul>" + getListItems(items) + "</ul>";
}

function getListItems(array) {
    return array.map(x => "<li>" + x + "</li>").join("");
}

function getFormattedCost(trip) {
    return "<span" + (trip.end > now ? " style=\"opacity: 0.5; color: grey;\"" : "") + ">" + trip.cost.toFixed(0) + " " + configuration.mainCurrency + "</span>";
}

function getAirportLink(location) {
    return "<a href=\"https://www.google.com/maps/search/Letiště " + location.name + "\">" + location.name + "</a>"; 
}

function getFlightLink(flight) {
    return "<a href=\"https://www.flightradar24.com/data/flights/" + flight + "\">" + flight + "</a>";
}

function resolveCountry(countryName) {
    return countryName;
}

function getDateString(timestamp, showYear = false) {
    const date = timestamp instanceof Date ? timestamp : new Date(timestamp * 1000);
    return date.getDate() + "." + (date.getMonth() + 1) + "." + (showYear ? date.getFullYear() : "");
}

function getTimeString(timestamp) {
    const date = timestamp instanceof Date ? timestamp : new Date(timestamp * 1000);
    return date.getHours() + ":" + (date.getMinutes() < 10 ? "0" : "") + date.getMinutes();
}

function getFromDateToDateString(startTimestamp, endTimestamp, keepHomeTimeZone, showYear) {
    const start = createDate(startTimestamp, keepHomeTimeZone);
    const end = createDate(endTimestamp - 1, keepHomeTimeZone);
    const isWithinSameMonth = start.getMonth() == end.getMonth(); 
    return (isWithinSameMonth ? (start.getDate() + ".") : getDateString(start)) + " - " + getDateString(end) + (showYear ? end.getFullYear() : ""); 
}

function getFlagImage(country) {
    return "<img style=\"width: 1em; height: 1em; vertical-align: -0.15em\" src=\"" + getFlagImageUrl(country) + "\">";
}

function getFlagImageUrl(country) {
    return "https://" + configuration.hostName + "/img/flags/" + configuration.countries[country].unicode + ".svg";
}

function createDate(timestamp, keepHomeTimeZone) {
    return keepHomeTimeZone ? new Date(new Date(timestamp * 1000).toLocaleString('en-US', { timeZone: configuration.defaultTimezone })) : new Date(timestamp * 1000);
}

function isDayTrips(trip) {
    return trip.name == configuration.specialTripNames.dayTrips;
}

function isEventTimeSet(event) {
    return (event.end - event.start != 86400) && (event.end - event.start != 86400 + 3600) && (event.end - event.start != 86400 - 3600);
}

function getGeneralTable(headerRowColumns, contentRowColumnsSelector, items, additionalRowColumns = undefined) {
    const headerRow = "<tr>" + headerRowColumns.map(column => getTableCell(column, true)).join("") + "</tr>";      
    const contentRows = items.map(item => "<tr>" + contentRowColumnsSelector(item).map(column => getTableCell(column, false)).join("") + "</tr>");
    const additionalRow = additionalRowColumns === undefined ? "" : ("<tr class=\"additionalRow\">" + additionalRowColumns.map(column => getTableCell(column, false)).join("") + "</tr>");      
    
    return "<table>" + headerRow + contentRows.join("") + additionalRow + "</table>";
}

function getTableCell(column, isHeader) {
    const elementTag = isHeader ? "th" : "td";
    return "<" + elementTag + (column.hideifSimplified ? " class=\"hideIfSimplified\"" : "") + (column.rowspan !== undefined ? " rowspan=\"" + column.rowspan + "\"" : "") + ">" + column.content + "</" + elementTag + ">";
}

function getCountriesWithoutLayovers(trip, places) {
    return getCountries(places.filter(place => trip.layovers.indexOf(place.id) == -1));
}

function getCountries(places) {
    return [...new Set(places.map(place => place.country))];
}

function getDistance(p1, p2) {
    const toRad = x => x * Math.PI / 180;
  
    var x1 = p2.latitude - p1.latitude;
    var x2 = p2.longitude - p1.longitude;
    var a = Math.sin(toRad(x1) / 2) * Math.sin(toRad(x1) / 2) + Math.cos(toRad(p1.latitude)) * Math.cos(toRad(p2.latitude)) * Math.sin(toRad(x2) / 2) * Math.sin(toRad(x2) / 2);
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return 6378 * c;
}

function getZonesDifference(timestamp, originTimezone, targetTimezone) {
    return (new Date(new Date(timestamp * 1000).toLocaleString('en-US', { timeZone: originTimezone })).getTime() - new Date(new Date(timestamp * 1000).toLocaleString('en-US', { timeZone: targetTimezone })).getTime()) / 1000;
}

// Array methods.
function sum(array) {
    return array.reduce((partialSum, a) => partialSum + a, 0);
}

function average(array) {
   return sum(array) / array.length;
}

function findMin(array, fn) {
    let min = Number.MAX_SAFE_INTEGER;
    let result = undefined;

    for (let i = 0; i < array.length; ++i) {
        const newMinCandidate = fn(array[i]);
        if (newMinCandidate < min) {
            min = newMinCandidate;
            result = array[i];
        }
    }

    return result;
}

function getOnlyElementOrDefault(array, val) {
    return array.length == 1 ? array[0] : val;
}

function getOnlyElement(array) {
    return getOnlyElementOrDefault(array, undefined);
}

function getFirstElement(array) {
    return array.length == 0 ? undefined : array[0];
}

function getLastElement(array) {
    return array.length == 0 ? undefined : array[array.length - 1];
}

function reversed(array) {
    const arr = [...array];
    arr.reverse();
    return arr;
}

function sorted(array, comparator = undefined) {
    const arr = [...array];
    if (comparator === undefined) {
        arr.sort();
    }
    else {
        arr.sort(comparator);
    }
    return arr;
}