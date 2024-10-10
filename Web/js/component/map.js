function initializeMapForTrip(id, trip, places) {
    return initializeMap(id, places.filter(place => trip.layovers.indexOf(place.id) == -1));
}

function initializeMapWithFlightPaths(id, flights, airports) {
    const map = initializeMap(id, airports);
    const routes = {};
    flights.map(flight => getFlightRoute(flight)).forEach(route => {
        if (!(route in routes)) {
            routes[route] = 0;
        }
        routes[route] = routes[route] + 1;
    })
    addFlightPaths(flights, routes, map);
    return map;
}

function initializeMap(id, places) {
    const map = new google.maps.Map(document.getElementById(id), { 
        zoom: 5, 
        center: { lat: configuration.homeLocation.latitude, lng: configuration.homeLocation.longitude }, 
        disableDefaultUI: true, 
        fullscreenControl: true, 
        styles: configuration.mapStyle
    });
    addMarkers(map, places, 1);
    return map;
}

function addMarkers(map, places) {
    const bounds = new google.maps.LatLngBounds();
    const markers = places.map(place => addMarker(place, map, bounds)).filter(marker => marker !== undefined);
    map.addListener("zoom_changed", () => {
        if (map.getZoom() > 20) {
            // This applies to places with a single marker only.
            map.setZoom(8);
        }
        changeScale(markers, map.getZoom());
    });
    return markers;
}

function addMarker(place, map, bounds) {
    const color = place.color !== undefined ? place.color : configuration.countries[place.country].color;
    const marker = new google.maps.Marker({ 
        map: map, 
        position: new google.maps.LatLng(parseFloat(place.latitude), parseFloat(place.longitude)), 
        title: getPlacePrettyName(place.name),
        icon: getFlagMarker(color, map.getZoom()) 
    });
    marker.addListener("click", _ => window.location = "place/" + place.name + "," + place.country);
    if (typeof(handleMarkerRightClick) === typeof(Function)) {
        marker.addListener("rightclick", _ => handleMarkerRightClick(place));
    }
    if (bounds !== undefined) {
        bounds.extend(marker.position);
        map.fitBounds(bounds);
    }
    return marker;
}

function getFlagMarker(color, currentZoom) {
    return {
        path: configuration.flagMarkerPath,
        strokeOpacity: (color == "#FFFFFF" ? 1 : 0.3),
        strokeWeight: computeStrokeWeight(currentZoom),
        strokeColor: "black",
        fillColor: color,
        fillOpacity: 1,
        rotation: 0,
        scale: computeMarkerScale(currentZoom),
        anchor: new google.maps.Point(19, 52)
    };
}

function computeMarkerScale(currentZoom) {
    var val = 0.1 + currentZoom * 0.1;
    if (val < 0.5) {
        val = 0.5;
    }
    if (val > 0.9) {
        val = 0.9;
    }
    return val;
}

function computeStrokeWeight(currentZoom) {
    var val = currentZoom * 0.1 - 0.1;
    if (val < 0.3) {
        val = 0.3;
    }
    if (val > 1.3) {
        val = 1.3;
    }
    return val;
}

function changeScale(markers, currentZoom) {
    markers.forEach(marker => {
        marker.icon.scale = computeMarkerScale(currentZoom);
        marker.icon.strokeWeight = computeStrokeWeight(currentZoom);
        marker.setIcon(marker.icon);
    });
}

function getFlightRoute(flight) {
    const airports = sorted([ flight.from.code, flight.to.code ]);
    return airports[0] + " - " + airports[1];
}

function addFlightPaths(flights, routes, map) {
    flights.forEach(flight => {
        let color = undefined;

        switch (routes[getFlightRoute(flight)]) {
            case 1:
                color = "#FFAC1C";
                break;
            case 2:
            case 3:
            case 4:
                color = "#FF0000";
                break;
            default:
                color = "#2222FF";
                break;
        }

        const flightPath = new google.maps.Polyline({
            path: [ 
                { lat: parseFloat(flight.from.latitude), lng: parseFloat(flight.from.longitude) }, 
                { lat: parseFloat(flight.to.latitude), lng: parseFloat(flight.to.longitude) } ],
            geodesic: true,
            strokeColor: color,
            strokeOpacity: 1.0,
            strokeWeight: 2,
          });

        flightPath.setMap(map);
    });
}