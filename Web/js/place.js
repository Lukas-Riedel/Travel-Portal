async function init(placeName, countryName, isLoggedIn) {
    const country = resolveCountry(countryName);
    const place = await getPlace(placeName, country);

    if (place === undefined) {
        location.replace("https://www.google.com/maps/search/" + placeName + ", " + country);
    }

    const places = await getPlaces(!isLoggedIn);

    // Title.
    document.title = getDocumentTitle(place);
    $('#name').html(getTitle(place, isLoggedIn));
    
    // Map.
    initializeMap("map", [ place ]);
    
    // Dates.
    $('#dates').html(getDatesComponent(place));
    
    // Categories.
    $('#categories').html(getCategoriesComponent(place));

    // Albums.
    $('#albums').html(getAlbumsComponentForPlace(place, isLoggedIn));

    // Nearby places.
    $('#nearbyPlaces').html(getAlbumsComponentWithTitle("Místa v okolí", getAlbumsComponentForNearbyPlaces(place, places)));

    // Footer.
    const placeId = await getResponse("GetPlaceIdentifier", { name: placeName, country: country });
    $('#footer').html(getFooter(isLoggedIn, [ 
        "<a onclick=\"changeLocation(" + placeId.id + ")\">Upravit polohu</a>", 
        "<a onclick=\"changeName(" + placeId.id + ")\">Přejmenovat</a>", 
        "<a onclick=\"changeExcerpt(" + placeId.id + ", '" + place.excerpt + "')\">Změnit excerpt</a>" ]));
}

function getDocumentTitle(place) {
    return configuration.countries[place.country].emoji + " " + getPlacePrettyName(place.name);
}

function getTitle(place) {
    return getFlagImage(place.country) + " " + getPlacePrettyName(place.name);
}

function getCategoriesComponent(place) {
    return getListComponent("Kategorie", place.categories.map(category => 
        "<a href=\"https://" + configuration.hostName + "/category/" + category.name + "\">" + getCategoryPrettyName(category.name) + "</a>"
    ));
}

function getDatesComponent(place) {
    return getListComponent("Termín", place.dates.map(date => date.trip).some(trip => trip === null)
        ? [ "Příliš mnoho termínů" ]
        : [...new Set(reversed(place.dates.map(getDateEntry)))]);
}

function getDateEntry(date) {
    return getDateString(date.start, true) + " (<a href=\"https://" + configuration.hostName + "/trip/" + getFullyQualifiedTripName(date.trip) + "\">" + getFullyQualifiedTripName(date.trip) + "</a>)";
}

function getLoginComponent(isLoggedIn) {
    const list = [ getLoginLink(isLoggedIn) ];
    return "<ul>" + getListItems(list) + "</ul>";
}

function getAlbumsComponentWithTitle(title, content) {
    return content == "" ? "" : ("<h4>" + title + "</h4>" + content);
}

async function changeLocation(placeId) {
    const address = prompt("Zadej novou adresu místa:");
    if (address == null || address == "") {
        return;
    }

    const resolvedAddress = await getCoords(address);
    if (confirm("Nalezené místo je ve státě " + resolvedAddress.country + " (" + resolvedAddress.latitude + ", " + resolvedAddress.longitude + "). Přeješ si toto místo přidat?")) {
        executeAndReload("ChangePlaceIdentifier", { placeId: placeId, latitude: resolvedAddress.latitude, longitude: resolvedAddress.longitude });
    }
}

async function changeName(placeId) {
    const name = prompt("Zadej nové jméno místa:");
    if (name == null || name == "") {
        return;
    }

    executeAndAlertConfirmation("ChangePlaceIdentifier", { placeId: placeId, name: name });
}

async function changeExcerpt(placeId, originalExcerpt) {
    const excerpt = prompt("Zadej nový excerpt místa:", originalExcerpt);
    if (excerpt == null || excerpt == "") {
        return;
    }

    executeAndAlertConfirmation("ChangePlaceIdentifier", { placeId: placeId, excerpt: excerpt });
}