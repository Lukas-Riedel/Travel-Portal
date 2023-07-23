async function init(isLoggedIn) {
    const places = await getPlaces(!isLoggedIn);
    const stats = await getStats();

    // Title.
    $('#title').html(getTitle(places));

    // Map.
    initializeMap("map", places);

    // Stats.
    $('#stats').html(getStatsComponent(stats));

    // Footer.
    $('#footer').html(getFooter(isLoggedIn));
}

function getTitle(places) {
    return "<img src=\"img/icon.jpg\"><br>Statistiky<br>" + getCountries(places).map(getFlagImage).join(" ");
}