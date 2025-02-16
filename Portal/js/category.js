async function init(categoryId, isLoggedIn) {
    const places = await api.listRegularPlaces(undefined, categoryId, undefined, undefined, isLoggedIn ? Number.MAX_SAFE_INTEGER : Math.round(now), "CATEGORIES");
    const category = await api.getCategory(categoryId);

    // Title.
    document.title = getDocumentTitle(category, places);
    $('#title').html(getTitle(category, places));
    
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
    return (category.metadata != null ? getEmoji(category.metadata.unicode) 
        : getCountries(places).map(country => configuration.countries[country].emoji).join("")) + " " + getCategoryPrettyName(category.name);
}

function getTitle(category, places) {
    return getCategoryPrettyName(category.name) + "<br>" + (category.metadata != null ? getFlagImageForUnicode(category.metadata.unicode)
        : getCountries(places).map(getFlagImage).join(" "));
}