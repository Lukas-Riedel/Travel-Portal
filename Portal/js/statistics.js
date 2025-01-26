async function init(isLoggedIn) {
    const places = await getPlaces(!isLoggedIn);
    const statistics = await api.listStatistics();

    // Title.
    $('#title').html(getTitle(places));

    // Map.
    initializeMap("map", places);

    // Stats.
    $('#statistics').html(getStatsComponent(statistics));

    // Footer.
    $('#footer').html(getFooter(isLoggedIn));
}

function getTitle(places) {
    return "<img src=\"img/icon.jpg\"><br>Statistiky<br>" + getCountries(places).map(getFlagImage).join(" ");
}