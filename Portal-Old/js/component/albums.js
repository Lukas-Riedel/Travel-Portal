function getAlbumsComponentForTrip(trip, places, showButtons) {
    places = sorted(places
        .filter(place => place.dates.map(date => date.album).filter(album => album != null).length > 0)
        .flatMap(place => place.dates.filter(date => date.album != null && date.album.imagesCount > 0).map(date => {
            return {
                id: place.id,
                name: place.name,
                country: place.country,
                start: date.start,
                tripId: date.trip.id,
                album: date.album
            }
        })), (a, b) => b.start - a.start);
    return getAlbumsComponent(places.map(place => {
        return {
            id: place.album.id,
            mainPhotoId: place.album.mainPhotoId,
            permalink: place.album.permalink,
            place: { id: place.id, name: place.name, country: place.country, tripId: place.tripId },
            tripName: getFullyQualifiedTripName(trip),
            nameTokens: [ getFlagImage(place.country), getPlacePrettyName(place.name), getDateString(place.start, true) ],
            action: "onclick=\"openGalleryForAlbum('" + place.album.id + "', " + place.id + ")\"",
            imageUrl: place.album.mainImageUrl
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
                tripId: date.trip == null ? null : date.trip.id,
                album: date.album
            }
        })), (a, b) => b.start - a.start);
    return getAlbumsComponent(places.map(place => {
        return {
            id: place.album.id,
            mainPhotoId: place.album.mainPhotoId,
            permalink: place.album.permalink,
            place: { id: place.id, name: place.name, country: place.country, tripId: place.tripId },
            nameTokens: [ getFlagImage(place.country), getPlacePrettyName(place.name), getDateString(place.start, true) ],
            action: "onclick=\"openGalleryForAlbum('" + place.album.id + "', " + place.id + ")\"",
            imageUrl: place.album.mainImageUrl
        };
     }), showButtons ? getButtonsForStandardAlbum : undefined);
}

function getAlbumsComponentForPhotos(placeId, albumId, photos, showButtons) {
    return getAlbumsComponent(photos.map(photo => {
        return {
            id: photo.id,
            mainPhotoId: photo.id,
            permalink: photo.permalink,
            place: { id: placeId },
            album: { id: albumId },
            index: photos.indexOf(photo) + 1,
            nameTokens: [],
            action: "",
            imageUrl: photo.url + "?w=" + configuration.albumThumbnailImageSize.width + "&h=" + configuration.albumThumbnailImageSize.height
        };
     }), showButtons ? getButtonsForPhotos : undefined);
}

function getAlbumsComponentForPlace(place, showButtons) {
    const places = sorted(place.dates.filter(date => date.album != null && date.album.imagesCount > 0).map(date => {
        return {
            id: place.id,
            name: place.name,
            country: place.country,
            start: date.start,
            tripId: date.trip === null ? undefined : date.trip.id,
            album: date.album
        }
    }), (a, b) => b.start - a.start);
    return getAlbumsComponent(places.map(place => {
        return {
            id: place.album.id,
            mainPhotoId: place.album.mainPhotoId,
            permalink: place.album.permalink,
            place: { id: place.id, name: place.name, country: place.country, tripId: place.tripId },
            nameTokens: [ getFlagImage(place.country), getPlacePrettyName(place.name), getDateString(place.start, true) ],
            action: "onclick=\"openGalleryForAlbum('" + place.album.id + "', " + place.id + ")\"",
            imageUrl: place.album.mainImageUrl
        };
     }), showButtons ? getButtonsForStandardAlbum : undefined);
}

function getAlbumsComponentForCategory(places) {
    places = sorted(places
        .filter(place => place.mainHighlight != null)
        .map(place => {     
            return {
                id: place.id,
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
            action: "href=\"https://" + location.hostname + "/new/place/" + place.id,            
            imageUrl: place.imageUrl
        };
     }), undefined);
}

function getAlbumsComponentForNearbyPlaces(referencePlace, places) {
    places = sorted(places
        .filter(place => place.mainHighlight != null)
        .map(place => {     
            return {
                id: place.id,
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
            action: "href=\"https://" + location.hostname + "/new/place/" + place.id + "\"",         
            imageUrl: place.imageUrl
        };
     }), undefined);
}

function getAlbumsComponentForTrips(trips) {
    return getAlbumsComponent(trips.map(trip => {
        return {
            nameTokens: isDayTrips(trip) ? [ getTripFlagImages(trip), getFullyQualifiedTripName(trip) ] : [ getTripFlagImages(trip), trip.name, getFromDateToDateString(trip.start, trip.end, true, true) ],
            action: "href=\"https://" + location.hostname + "/trip/" + trip.id + "\"",
            imageUrl: trip.mainHighlight == null ? trip.id : trip.mainHighlight.url.thumbnail
        }
    }), undefined);
}

function getAlbumsComponentForCountries(countryCategories, places) {
    const countryImages = countryCategories.filter(c => c.mainHighlight != null).reduce(function(map, obj) {
        map[obj.name] = obj.mainHighlight.url.thumbnail;
        return map;
    }, {});
    const countryIds = countryCategories.reduce(function(map, obj) {
        map[obj.name] = obj.id;
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
            action: "href=\"https://" + location.hostname + "/category/" + countryIds[country.name] + "\"",            
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
        const innerRows = [ "<div style=\"width: " + configuration.albumThumbnailImageSize.width + "px;\" class=\"albumWrapper\"><a " + album.action + "\"><img style=\"width: " + configuration.albumThumbnailImageSize.width + "px; height: " + configuration.albumThumbnailImageSize.height + "px;\" src=\"" + album.imageUrl + "\">" + (album.nameTokens.length == 0 ? "" : overlay) + "</a></div>" ];
        
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
            action: "window.open('../place/" + album.place.id + "/album/" + album.id + "', '_blank')",
            image: "img/edit.png"
        },
        { 
            action: "refreshAlbum(" + album.place.id + ", " + album.id + ")",
            image: "img/refresh.png"
        },
        { 
            action: "changePlaceMainHighlight(" + album.place.id + ", " + album.mainPhotoId + ")",
            image: "img/heart.png"
        },
        { 
            action: "changeCountryMainHighlight('" + album.place.id + "', " + album.mainPhotoId + ")",
            image: getFlagImageUrl(album.place.country)
        }
    ];
}

function getButtonsForPhotos(photo) {
    return [
        { 
            action: "window.open('" + photo.permalink + "', '_blank')",
            image: "../../../img/photo.png"
        },
        { 
            action: "refreshAlbum(" + photo.place.id + ", " + photo.album.id + ", " + photo.index + ")",
            image: "../../../img/heart.png"
        },
        { 
            action: "replacePhoto(" + photo.place.id + ", " + photo.album.id + ", " + photo.id + ", '" + photo.permalink + "')",
            image: "../../../img/edit.png"
        },
    ];
}

function getButtonsForStandardAlbumInTrip(album) {
    const result = getButtonsForStandardAlbum(album);
    result.push(        
        { 
            action: "changeTripMainHighlight(" + album.place.tripId + ", " + album.mainPhotoId + ")",
            image: "img/organized_tour.png"
    });
    return result;
}

async function openGalleryForAlbum(albumId, placeId) {
    openGallery((await api.listPlaceAlbumPhotos(placeId, albumId)).map(image => image.url).map(url => url + "=w" + $(window).width() + "-h" + $(window).height()));
}

function openGallery(images) {
    const lightbox = new FsLightbox();
    lightbox.props.type = "image";
    lightbox.props.sources = images;
    lightbox.open();
}

async function createAlbum(id, start) {
    const album = await api.createPlaceAlbum(id, start);
    if ("permalink" in album) {
        window.open(album.permalink, '_blank').focus();
    }
    reload();
}

async function refreshAlbum(placeId, albumId, mainPhotoPosition = undefined) {
    api.refreshPlaceAlbum(placeId, albumId, mainPhotoPosition).then(reload);
}

async function changePlaceMainHighlight(placeId, photoId) {
    const highlight = await api.createPlaceHighlight(placeId, photoId);
    api.updatePlaceMainHighlight(placeId, highlight.id).then(reload);
}

async function changeCountryMainHighlight(countryPlaceId, photoId) {
    const place = await api.getPlace(countryPlaceId);
    const categoryId = getOnlyElement(place.categories.filter(category => category.category === "COUNTRY")).id;
    const highlight = await api.createCategoryHighlight(categoryId, photoId);
    api.updateCategoryMainHighlight(categoryId, highlight.id).then(reload);
}

async function changeTripMainHighlight(tripId, photoId) {
    const highlight = await api.createTripHighlight(tripId, photoId);
    api.updateTripMainHighlight(tripId, highlight.id).then(reload);
}

function getTripFlagImages(trip) {
    return "<ul class=\"tripFlags\">" + getListItems(trip.countries.map(getFlagImage)) + "</ul>";
}

async function replacePhoto(placeId, albumId, replacedPhotoId, replacedPhotoPermalink) {
    const path = prompt("Zadej cestu ke složce s fotkou k nahrání:");
    if (path == null || path == "") {
        return;
    }

    await api.createEvent("PhotoReplacing", { placeId: placeId, albumId: albumId, replacedPhotoId: replacedPhotoId, path: path });

    window.open(replacedPhotoPermalink, '_blank').focus();
}