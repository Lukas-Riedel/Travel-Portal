function getAlbumsComponentForTrip(trip, places, showButtons) {
    places = sorted(places
        .filter(place => place.dates.map(date => date.album).filter(album => album != null).length > 0)
        .flatMap(place => place.dates.filter(date => date.album != null && date.album.imagesCount > 0).map(date => {
            return {
                id: place.id,
                name: place.name,
                country: place.country,
                start: date.start,
                album: date.album
            }
        })), (a, b) => b.start - a.start);
    return getAlbumsComponent(places.map(place => {
        return {
            id: place.album.id,
            permalink: place.album.permalink,
            place: { id: place.id, name: place.name, country: place.country },
            tripName: getFullyQualifiedTripName(trip),
            nameTokens: [ getFlagImage(place.country), getPlacePrettyName(place.name), getDateString(place.start, true) ],
            action: "onclick=\"openGalleryForAlbum('" + place.album.id + "', " + place.id + ")\"",
            imageUrl: place.album.mainImageUrl,
            isLowQuality: place.album.isLowQuality,
            isBadWeather: place.album.isBadWeather
        };
     }), showButtons ? getButtonsForStandardAlbumInTrip : undefined);
}

function getAlbumsComponentForYear(places, showButtons) {
    places = sorted(places
        .filter(place => place.dates.map(date => date.album).filter(album => album != null).length > 0)
        .flatMap(place => place.dates.filter(date => date.album != null && date.album.imagesCount > 0).map(date => {
            return {
                id: place.id,
                name: place.name,
                country: place.country,
                start: date.start,
                album: date.album
            }
        })), (a, b) => b.start - a.start);
    return getAlbumsComponent(places.map(place => {
        return {
            id: place.album.id,
            permalink: place.album.permalink,
            place: { id: place.id, name: place.name, country: place.country },
            nameTokens: [ getFlagImage(place.country), getPlacePrettyName(place.name), getDateString(place.start, true) ],
            action: "onclick=\"openGalleryForAlbum('" + place.album.id + "', " + place.id + ")\"",
            imageUrl: place.album.mainImageUrl,
            isLowQuality: place.album.isLowQuality,
            isBadWeather: place.album.isBadWeather
        };
     }), showButtons ? getButtonsForStandardAlbum : undefined);
}

function getAlbumsComponentForPlace(place, showButtons) {
    const places = sorted(place.dates.filter(date => date.album != null && date.album.imagesCount > 0).map(date => {
        return {
            id: place.id,
            name: place.name,
            country: place.country,
            start: date.start,
            album: date.album
        }
    }), (a, b) => b.start - a.start);
    return getAlbumsComponent(places.map(place => {
        return {
            id: place.album.id,
            permalink: place.album.permalink,
            place: { id: place.id, name: place.name, country: place.country },
            nameTokens: [ getFlagImage(place.country), getPlacePrettyName(place.name), getDateString(place.start, true) ],
            action: "onclick=\"openGalleryForAlbum('" + place.album.id + "', " + place.id + ")\"",
            imageUrl: place.album.mainImageUrl,
            isLowQuality: place.album.isLowQuality,
            isBadWeather: place.album.isBadWeather
        };
     }), showButtons ? getButtonsForStandardAlbum : undefined);
}

function getAlbumsComponentForCategory(places) {
    places = sorted(places
        .filter(place => place.dates.map(date => date.album).filter(album => album != null).length > 0)
        .map(place => {     
            return {
                name: place.name,
                country: place.country,
                imagesCount: place.imagesCount,
                imagesScore: place.imagesScore,
                imageUrl: place.mainHighlight.url.thumbnail
            }
        }).filter(place => place.imagesScore > 0), (a, b) => b.imagesScore - a.imagesScore);
    places.forEach(place => console.log(place.name + " - " + place.imagesScore + " (" + place.imagesCount + ")"));
    return getAlbumsComponent(places.map(place => {
        return {
            nameTokens: [ getFlagImage(place.country), getPlacePrettyName(place.name) ],
            action: "href=\"https://" + configuration.hostName + "/place/" + place.name + "," + place.country,            
            imageUrl: place.imageUrl
        };
     }), undefined);
}

function getAlbumsComponentForNearbyPlaces(referencePlace, places) {
    places = sorted(places
        .filter(place => place.dates.map(date => date.album).filter(album => album != null).length > 0)
        .map(place => {     
            return {
                name: place.name,
                country: place.country,
                distance: Math.round(getDistance(referencePlace, place)),
                imagesCount: place.imagesCount,
                imagesScore: place.imagesScore,
                imageUrl: place.mainHighlight.url.thumbnail
            }
        }).filter(place => place.imagesScore > 0).filter(place => place.distance > 0), (a, b) => a.distance - b.distance).slice(0, Math.max(configuration.minimumNearbyPlacesCount, configuration.albumsPerRow));
    return getAlbumsComponent(places.map(place => {
        return {
            nameTokens: [ getFlagImage(place.country), getPlacePrettyName(place.name), formatKilometersCount(place.distance) ],
            action: "href=\"https://" + configuration.hostName + "/place/" + place.name + "," + place.country + "\"",         
            imageUrl: place.imageUrl
        };
     }), undefined);
}

function getAlbumsComponentForTrips(trips) {
    return getAlbumsComponent(trips.map(trip => {
        return {
            nameTokens: isDayTrips(trip) ? [ getTripFlagImages(trip), getFullyQualifiedTripName(trip) ] : [ getTripFlagImages(trip), trip.name, getFromDateToDateString(trip.start, trip.end, true, true) ],
            action: "href=\"https://" + configuration.hostName + "/trip/" + getFullyQualifiedTripName(trip) + "\"",
            imageUrl: trip.mainHighlight.url.thumbnail
        }
    }), undefined);
}

function getAlbumsComponentForCountries(countryCategories, places) {
    const countryImages = countryCategories.filter(c => c.mainHighlight != null).reduce(function(map, obj) {
        map[obj.name] = obj.mainHighlight.url.thumbnail;
        return map;
    }, {});
    places = sorted(places
        .filter(place => place.dates.map(date => date.album).filter(album => album != null).length > 0)
        .map(place => {
            return {
                name: place.name,
                country: place.country,
                imagesCount: place.imagesCount,
                imagesScore: place.imagesScore
            };
        }).filter(place => place.imagesScore > 0), (a, b) => b.imagesScore - a.imagesScore);
    const countriesMap = {};
    places.forEach(place => {
        if (!(place.country in countriesMap)) {
            countriesMap[place.country] = {
                name: place.country,
                imagesCount: place.imagesCount,
                imagesScore: place.imagesScore
            };
        }
        else {
            countriesMap[place.country].imagesCount += place.imagesCount;
            countriesMap[place.country].imagesScore += place.imagesScore;
        }
    });
    const countriesArray = sorted(Object.values(countriesMap), (a, b) => b.imagesScore - a.imagesScore);
    countriesArray.forEach(country => console.log(country.name + " - " + country.imagesScore + " (" + country.imagesCount + ")"));
    return getAlbumsComponent(countriesArray.map(country => {
        return {
            id: undefined,
            permalink: undefined,
            place: undefined,
            nameTokens: [ getFlagImage(country.name), country.name ],
            action: "href=\"https://" + configuration.hostName + "/category/" + country.name + "\"",            
            imageUrl: countryImages[country.name]
        };
     }), undefined);
}

function getAlbumsComponent(albums, buttonsSupplier) {
    let table = "";
    let row = "";
    let index = 0;

    const alreadyContained = [];

    albums.forEach(album => {
        if (alreadyContained.indexOf(album.imageUrl) !== -1) {
            return;
        }

        if (index++ % configuration.albumsPerRow == 0 && index !== 1) {
            table += "<tr>" + row + "</tr>";
            row = "";
        }

        const overlay = "<div class=\"overlay\"><ul>" + album.nameTokens.map(nameToken => "<li>" + nameToken + "</li>").join("") + "</ul></div>";
        const innerRows = [ "<div style=\"width: " + configuration.highlightThumbnailImageSize.width + "px;\" class=\"albumWrapper\"><a " + album.action + "\"><img style=\"width: " + configuration.highlightThumbnailImageSize.width + "px; height: " + configuration.highlightThumbnailImageSize.height + "px;\" src=\"" + album.imageUrl + "\">" + (album.nameTokens.length == 0 ? "" : overlay) + "</a></div>" ];
        
        if (buttonsSupplier !== undefined) {            
            innerRows.push("<div class=\"utilitiesColumn\">" + buttonsSupplier(album).map(button => "<a onclick=\"" + button.action + "\"><img style=\"width: 24px;\" src=\"" + button.image + "\"></a>").join("") + "</div>");
        }

        alreadyContained.push(album.imageUrl);

        row += "<td>" + innerRows.join("<br>") + "</td>";

    });
    
    if (row !== "") {
        table += "<tr>" + row + "</tr>";
    }

    return table == "" ? "" : ("<table class=\"gallery\">" + table + "</table>");
}

function getButtonsForStandardAlbum(album) {
    return [
        { 
            action: "window.open('" + album.permalink + "', '_blank')",
            image: "img/photo.png"
        },
        { 
            action: "refreshAlbum('" + album.id + "')",
            image: "img/refresh.png"
        },
        { 
            action: "changeAlbumStatus('BAD_WEATHER', '" + album.id + "', " + album.place.id + ")",
            image: !album.isBadWeather ? "img/good_weather.png" : "img/bad_weather.png"
        },
        { 
            action: "changeAlbumStatus('LOW_QUALITY', '" + album.id + "', " + album.place.id + ")",
            image: !album.isLowQuality ? "img/good_quality.png" : "img/low_quality.png"
        },
        { 
            action: "changeAlbumStatus('MAIN_FOR_PLACE', '" + album.id + "', " + album.place.id + ")",
            image: "img/heart.png"
        },
        { 
            action: "changeAlbumStatus('MAIN_FOR_COUNTRY', '" + album.id + "', " + album.place.id + ")",
            image: getFlagImageUrl(album.place.country)
        }
    ];
}

function getButtonsForStandardAlbumInTrip(album) {
    const result = getButtonsForStandardAlbum(album);
    result.push(        
        { 
            action: "changeAlbumStatus('MAIN_FOR_TRIP', '" + album.id + "', " + album.place.id + ")",
            image: "img/organized_tour.png"
    });
    return result;
}

async function openGalleryForAlbum(albumId, placeId) {
    openGallery((await getAlbumContents(albumId, placeId)).map(image => image.url).map(url => url + "=w" + $(window).width() + "-h" + $(window).height()));
}

function openGallery(images) {
    const lightbox = new FsLightbox();
    lightbox.props.type = "image";
    lightbox.props.sources = images;
    lightbox.open();
}

async function createAlbum(id, start) {
    execute("AddAlbum", { placeId: id, timestamp: start }, album => {
        if ("permalink" in album) {
            window.open(album.permalink, '_blank').focus();
        }
        location.reload();
    });
}

async function refreshAlbum(albumId) {
    executeAndReload("UpdateAlbum", { forceOverwrite: true, albumId : albumId });
}

async function changeAlbumStatus(type, albumId, placeId) {
    executeAndAlertConfirmation("ChangeAlbumStatus", { type: type, albumId: albumId, placeId: placeId});
}

function getTripFlagImages(trip) {
    return "<ul class=\"tripFlags\">" + getListItems(trip.countries.map(getFlagImage)) + "</ul>";
}