async function init(placeId, albumId, isLoggedIn) {
    const place = await api.getPlace(placeId);

    if (place === undefined || place.dates.every(date => date.start > now)) {
        location.replace("https://www.google.com/maps/search/" + place.name + ", " + place.country);
    }

    const photos = await api.listPlaceAlbumPhotos(placeId, albumId);

    // Title.
    document.title = getDocumentTitle(place);

    // Photos.
    $('#photos').html(getAlbumsComponentForPhotos(placeId, albumId, photos, isLoggedIn));
}

function getDocumentTitle(place) {
    return configuration.countries[place.country].emoji + " " + getPlacePrettyName(place.name);
}
