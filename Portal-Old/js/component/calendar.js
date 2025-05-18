let forecastLastUpdateTime = 0;
let selectedDates = [];

function getCalendarComponentForTrip(trip, places, isLoggedIn) {
    return doGetCalendarComponent(getCalendarDatesForTrip(trip, places, isLoggedIn), false);
}

function getCalendarComponentForTripCandidate(places) {
    return doGetCalendarComponent(getCalendarDatesForTripCandidate(places), false);
}

function getCalendarDatesForTrip(trip, places, isLoggedIn) {
    const calendar = {};
    places.forEach(place => {
        place.dates.forEach(placeDate => {
            const date = new Date(createDate(placeDate.start, false).toDateString());
            if (!(date in calendar)) {
                calendar[date] = [];
            }
            calendar[date].push({
                id: place.id,
                name: place.name,
                start: placeDate.start,
                end: placeDate.end,
                latitude: place.latitude,
                longitude: place.longitude,
                country: place.country,
                weather: placeDate.weather,
                sun: placeDate.sun,
                album: placeDate.album
            });
        });
    });
    
    const dates = sorted(Object.keys(calendar), (a, b) => new Date(a) - new Date(b));
    dates.forEach(date => calendar[date].sort((a, b) => a.start - b.start));

    return dates.map(date => {
        const convertedDate = new Date(date);
        
        calendar[date].forEach(place => {
            if (place.weather != null && place.weather.clouds != null) {
                console.log(place.name + " - " + place.weather.temperature + " °C - " + place.weather.clouds + " % - " + place.weather.precipitation + " mm/h");
            }
        });

        return {
            date: convertedDate.getTime() / 1000, // Apparently important for featured trips only.
            title: getDayOfWeek(convertedDate) + " " + getDateString(convertedDate),
            calendar: getCalendarEntry(calendar[date], isLoggedIn ? getPlaceButtonsForTripEntry : undefined, undefined),
            weather: getForecastEntry(calendar[date].map(place => place.weather).filter(weather => weather != null), calendar[date].map(place => place.sun).filter(sun => sun != null)),
            fitness: convertedDate.getTime() / 1000 < now ? getFitnessEntry(trip.fitness[dates.indexOf(date)], trip.fitness) : ""
        };
    });    
}

function getCalendarDatesForTripCandidate(places) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const calendar = {};
    places.forEach(place => {
        place.dates.forEach(placeDate => {
            const date = Math.floor(placeDate.start / 86400) + 1;
            if (!(date in calendar)) {
                calendar[date] = [];
            }
            calendar[date].push({
                name: place.name,
                start: today.getTime() / 1000 + placeDate.start + getTimeZoneOffset(new Date(), place.timezone),
                end: today.getTime() / 1000 + placeDate.end + getTimeZoneOffset(new Date(), place.timezone),
                latitude: place.latitude,
                longitude: place.longitude,
                country: place.country
            });
        });
    });
    
    const dates = sorted(Object.keys(calendar), (a, b) => a - b);
    dates.forEach(date => calendar[date].sort((a, b) => a.start - b.start));

    return dates.map(date => {
        return {
            date: date,
            title: "Den " + date,
            calendar: getCalendarEntry(calendar[date], undefined, undefined),
            weather: "",
            fitness: ""
        };
    });    
}

function doGetCalendarComponent(dates, showCheckboxes) {
    const calendarEntriesPerRow = getCalendarEntriesPerRow(dates.length);
    
    const tables = [];
    let headerRow = "";
    let itineraryRow = "";
    let forecastRow = "";
    let fitnessRow = "";
    let index = 0;

    dates.forEach(date => {
        if (index++ % calendarEntriesPerRow == 0 && index !== 1) {
            let table = "";

            table += "<tr>" + headerRow + "</tr>";
            headerRow = "";
            
            table += "<tr>" + itineraryRow + "</tr>";
            itineraryRow = "";
            
            if (!isEmptyTableRow(forecastRow)) {
                table += "<tr>" + forecastRow + "</tr>";
            }
            forecastRow = "";
            
            if (!isEmptyTableRow(fitnessRow)) {
                table += "<tr>" + fitnessRow + "</tr>";
            }
            fitnessRow = "";

            tables.push(table);
        }

        headerRow += "<th>" + (showCheckboxes ? "<input id=\"selectDateCheckbox" + (index) + "\" type=\"checkbox\" onclick=\"selectDate(" + index + ", " + serializeForHtmlAttribute(date.title) + ")\"/> " : "") + date.title + "</th>";
        itineraryRow += "<td>" + date.calendar + "</td>";        
        forecastRow += "<td>" + date.weather + "</td>";
        fitnessRow += "<td>" + date.fitness + "</td>";
    });    
    
    let table = "";

    if (headerRow !== "") {
        table += "<tr>" + headerRow + "</tr>";
    }
    
    if (itineraryRow !== "") {
        table += "<tr>" + itineraryRow + "</tr>";
    }
    
    if (forecastRow !== "" && !isEmptyTableRow(forecastRow)) {
        table += "<tr>" + forecastRow + "</tr>";
    }
    
    if (fitnessRow !== "" && !isEmptyTableRow(fitnessRow)) {
        table += "<tr>" + fitnessRow + "</tr>";
    }
    
    tables.push(table);

    return tables.map(table => "<table>" + table + "</table>");
}

function selectDate(checkboxIndex, date) {
    const checkbox = document.getElementById("selectDateCheckbox" + checkboxIndex);
    if (checkbox.checked) {
        selectedDates.push(date);
    }
    else {
        const index = selectedDates.indexOf(date);
        if (index !== -1) {
            selectedDates.splice(index, 1);
        }
    }
}

function getCalendarEntriesPerRow(totalEntries) {
    return totalEntries < configuration.maximumCalendarEntriesPerRow ? Math.round(totalEntries) : getCalendarEntriesPerRow(totalEntries / 2);
}

function getCalendarEntry(places, singlePlaceButtonsSupplier, allPlacesButtonSupplier) {
    const rows = places.map(place => {
        const rows = [ (isEventTimeSet(place) ? (getTimeString(place.start) + " ") : "") + getPlaceLink(place) ];

        if (singlePlaceButtonsSupplier !== undefined) {
            const buttons = singlePlaceButtonsSupplier(place);
            rows.push("<div class=\"utilitiesColumn\">" + buttons.map(button => "<a onclick=\"" + button.action + "\"><img style=\"width: 24px;\" src=\"" + button.image + "\"></a>").join("") + "</div>")
        }

        return rows.join("<br>");
    });

    if (allPlacesButtonSupplier !== undefined) {
        const buttons = allPlacesButtonSupplier(places);
        rows.push("<div class=\"utilitiesColumn\">" + buttons.map(button => "<a onclick=\"" + button.action + "\"><img style=\"width: 24px;\" src=\"" + button.image + "\"></a>").join("") + "</div>")
    }
    
    return rows.join("<br>");
}

function getPlaceButtonsForTripEntry(place) {
    const buttons = [];

    if (place.album === null) {
        buttons.push(
            { 
                action: "createAlbum(" + place.id + ", " + place.start + ')"',
                image: "img/add.png"
            }
        );
        buttons.push(
            { 
                action: "createAlbumAndUploadPhotos(" + place.id + ", " + place.start + ')"',
                image: "img/upload.png"
            }
        );
    }
    else {
        buttons.push(
            { 
                action: "window.open('" + place.album.permalink + "', '_blank')",
                image: "img/photo.png"
            }
        );
        buttons.push(
            {
                action: "refreshAlbum(" + place.id + ", " + place.album.id + ")",
                image: "img/refresh.png"
            }
        );
        buttons.push(
            { 
                action: "uploadPhotos(" + place.id + ", " + place.album.id + ')"',
                image: "img/upload.png"
            }
        );
    }

    return buttons;
}

function getForecastEntry(forecasts, suns) {
    if (forecasts.length == 0 || suns.length == 0) {
        return "";
    }
    
    const sun = toRangeString({ min: getTimeString(suns[0].sunrise), max: getTimeString(suns[suns.length - 1].sunset) });
    const altitude = toRangeString({ min: suns[0].altitude.start.toFixed(1), max: suns[suns.length - 1].altitude.end.toFixed(1) + "°" });
    
    forecasts.sort((a, b) => (b.precipitation - a.precipitation) || (b.clouds - a.clouds) || (a.temperature - b.temperature));
    
    const temperature = toRangeString(findMinAndMax(forecasts.map(forecast => forecast.temperature))) + " °C";
    const clouds = toRangeString(findMinAndMax(forecasts.map(forecast => forecast.clouds))) + " %";
    const precipitation = toRangeString(findMinAndMax(forecasts.map(forecast => forecast.precipitation))) + " mm/h";
    const wind = toRangeString(findMinAndMax(forecasts.map(forecast => forecast.wind))) + " m/s";

    const isHistoricalForecast = forecasts[0].symbol === null;
    const imageUrl = (isHistoricalForecast || forecasts[0].symbol === "") ? "img/question_mark.png" : ("img/weather/" + forecasts[0].symbol + ".svg");

    updateForecastLastUpdateTime(forecasts);

    return "<div class=\"forecast\">"
        + "<img src=\"" + imageUrl + "\"><br>"
        + "<span class=\"temperature\">" + temperature + "</span><br>"
        + "<div class=\"details\">" + (isHistoricalForecast ? "??? %" : clouds) + "<br>" + precipitation + "<br>" + wind + "<br>" + sun + "<br>" + altitude + "</div>"
        + "</div>";
}

function getFitnessEntry(fitness, allFitnessData) {
    if (fitness === undefined) {
        return "";
    }

    const steps = fitness.steps + " kroků";
    const distance = (Math.round(fitness.distance / 10) / 100) + " kilometrů";
    const minutes = Math.round(fitness.seconds / 60) + " minut";
    const calories = Math.round(fitness.calories) + " kcal";

    return "<div class=\"fitness\">"
        + "<img src=\"" + resolveIcon(fitness, allFitnessData) + "\"><br>"
        + "<span class=\"steps\">" + steps + "</span><br>"
        + "<div class=\"details\">" + distance + "<br>" + minutes + "<br>" + calories + "</div>"
        + "</div>";
}

function resolveIcon(fitness, allFitnessData) {
    if (allFitnessData.length === 1) {
        return "img/yellow_step.png";
    }

    const averageSteps = average(allFitnessData.filter(f => f != fitness).map(f => f.steps));
    const stepsShare = fitness.steps / averageSteps;

    if (stepsShare > 1.2) {
        return "img/blue_step.png";
    }
    if (stepsShare < 0.8) {
        return "img/red_step.png";
    }
    return "img/green_step.png";
}

function updateForecastLastUpdateTime(forecasts) {
    const max = Math.max(...forecasts.map(forecast => forecast.lastUpdate));
    if (max > forecastLastUpdateTime) {
        forecastLastUpdateTime = max;
    }    
}

function getPlaceLink(place) {
    return "<a href=\"https://" + location.hostname + "/new/place/" + place.id + "\">" + getPlacePrettyName(place.name) + "</a>";
}

function findMinAndMax(values) {
    return { min: (Math.round(Math.min(...values) * 10) / 10), max: (Math.round(Math.max(...values) * 10) / 10) };
}

function isEmptyTableRow(row) {
   return row.match("^(<td><\/td>)*$");
}

function getDayOfWeek(date) {
    switch (date.getDay()) { case 0: return "Ne"; case 1: return "Po"; case 2: return "Út"; case 3: return "St"; case 4: return "Čt"; case 5: return "Pá"; case 6: return "So"; }
}

function getTimeZoneOffset(date, timeZone) {
    let iso = date.toLocaleString('en-CA', { timeZone, hour12: false }).replace(', ', 'T');
    iso += '.' + date.getMilliseconds().toString().padStart(3, '0');
    const lie = new Date(iso);
    return -(lie - date) / 1000;
}

async function uploadPhotos(placeId, albumId) {
    const path = prompt("Zadej cestu ke složce s fotkami k nahrání:");
    if (path == null || path == "") {
        return;
    }

    const args = { placeId: placeId, albumId: albumId, path: path };
    
    const mainPhotoPosition = prompt("Zadej pozici hlavní fotky alba (nebo ponech prázdné):");
    if (path != null && path != "") {
        args["mainPhotoPosition"] = mainPhotoPosition;
    }

    return processPhotosUploadRequest(args);
}

async function createAlbumAndUploadPhotos(placeId, timestamp) {
    const path = prompt("Zadej cestu ke složce s fotkami k nahrání:");
    if (path == null || path == "") {
        return;
    }

    const args = { placeId: placeId, timestamp: timestamp, path: path };
    
    const mainPhotoPosition = prompt("Zadej pozici hlavní fotky alba (nebo ponech prázdné):");
    if (path != null && path != "") {
        args["mainPhotoPosition"] = mainPhotoPosition;
    }

    return processPhotosUploadRequest(args);
}

async function processPhotosUploadRequest(args) {
    api.createEvent("PhotosUploading", args).then(alertConfirmation);
}