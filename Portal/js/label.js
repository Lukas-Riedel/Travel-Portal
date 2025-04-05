async function init(label, isLoggedIn) {
    const places = await api.listRegularPlaces(undefined, undefined, label, undefined, undefined, isLoggedIn ? Number.MAX_SAFE_INTEGER : Math.round(now), "CATEGORIES");

    // Title.
    document.title = getDocumentTitle(label, places);
    $('#title').html(getTitle(label, places));
    
    // Map.
    initializeMap("map", places);

    // Albums.
    $('#albums').html(getAlbumsComponentForCategory(places));

    // Footer.
    $('#footer').html(getLoginLink(isLoggedIn));
}

function getDocumentTitle(label, places) {
    return getCountries(places).map(country => configuration.countries[country].emoji).join("") + " " + label;
}

function getTitle(label, places) {
    return label + "<br>" + getCountries(places).map(getFlagImage).join(" ");
}