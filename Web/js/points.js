async function init(placeName, countryName) {
    const place = await getResponse("GetPlaceIdentifier", { name: placeName, country: countryName });

    const points = await getSuggestedMapPoints(place.id);

    // Title.
    document.title = placeName;

    // Map.
    initializeMap("mobileMap", points);

    // Points.
    $('#points').html(getPoints(points));
}

function getPoints(points) {
    return "<ol>" + getListItems(points.map((point => "<strong style=\"color: " + point.color + "\">" + point.name + "</strong> - " + point.description))) + "</ol>";
}