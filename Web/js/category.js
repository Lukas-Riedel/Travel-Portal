async function init(category, isLoggedIn) {
    const places = await getPlacesForCategory(category, !isLoggedIn);
    const stats = await getStatsForCategory(category);

    // Title.
    document.title = getDocumentTitle(getCategoryPrettyName(category), places);
    $('#title').html(getTitle(getCategoryPrettyName(category), places));
    
    // Map.
    initializeMap("map", places);

    // Albums.
    $('#albums').html(getAlbumsComponentForCategory(places));

    // Stats.
    $('#stats').html(getStatsComponent(stats));

    // Footer.
    $('#footer').html(getLoginLink(isLoggedIn));
}

function getDocumentTitle(category, places) {
    return getCountries(places).map(country => configuration.countries[country].emoji).join("") + " " + category;
}

function getTitle(category, places) {
    return category + "<br>" + getCountries(places).map(getFlagImage).join(" ");
}