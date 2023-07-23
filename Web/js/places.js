async function init(isLoggedIn) {
    const places = await getPlaces(!isLoggedIn);

    // Title.
    $('#title').html(getTitle(places));

    // Main menu.
    $('#mainMenu').html(getMainMenu());

    // Map.
    initializeMap("map", places);

    // Albums.
    $('#albums').html(getAlbumsComponentForCountries(places));

    // Footer.
    $('#footer').html(getFooter(isLoggedIn));
}

function getTitle(places) {
    return "<img src=\"img/icon.jpg\"><br>" + getCountries(places).map(getFlagImage).join(" ");
}