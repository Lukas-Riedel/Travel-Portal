async function init(categoryId, isLoggedIn) {
    const places = await api.listRegularPlaces(undefined, categoryId, undefined, undefined, isLoggedIn ? Number.MAX_SAFE_INTEGER : Math.round(now));
    const category = await api.getCategory(categoryId);

    // Title.
    document.title = getDocumentTitle(getCategoryPrettyName(category.name), places);
    $('#title').html(getTitle(getCategoryPrettyName(category.name), places));
    
    // Map.
    initializeMap("map", places);

    // Albums.
    $('#albums').html(getAlbumsComponentForCategory(places));

    // Stats.
    $('#statistics').html(getStatsComponent(category.statistics));

    // Footer.
    $('#footer').html(getLoginLink(isLoggedIn));
}

function getDocumentTitle(category, places) {
    return getCountries(places).map(country => configuration.countries[country].emoji).join("") + " " + category;
}

function getTitle(category, places) {
    return category + "<br>" + getCountries(places).map(getFlagImage).join(" ");
}