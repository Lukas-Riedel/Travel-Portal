async function init(categoryId, isLoggedIn) {
    const candidatePlaces = await api.listCandidatePlaces(undefined, categoryId, undefined, true);

    // Title.
    $('#title').html(getTitle(isLoggedIn, candidatePlaces));

    // Map.
    initializeMap("map", candidatePlaces);

    // Content.
    $('#main').html(getMain(candidatePlaces, isLoggedIn));

    // Footer.
    $('#footer').html(getFooter(isLoggedIn));
}

function getTitle(isLoggedIn, places) {
    return "<img src=\"img/icon.jpg\"><br>" + (isLoggedIn ? "<a onclick=\"addPlaceCandidate()\">Plán</a>" : "Plán") + "<br>" + getCountries(places).map(getFlagImage).join(" ");
}

function getMain(places, showButtons) {
    const headerRowColumns = [
        { hideifSimplified: false, content: "Název"},
        { hideifSimplified: false, content: "Stát"},
        { hideifSimplified: true, content: "Poslední návštěva"}
    ];

    if (showButtons) {
        headerRowColumns.push({ hideifSimplified: false, content: "" });
    }

    const contentRowColumnsSelector = place => {
        const buttons = [];

        if (place.dates.length === 0) {
            buttons.push(
            { 
                action: "changeName(" + place.id + ")",
                image: "img/edit.png"
            },
            {
                action: "removeCandidatePlace(" + place.id + ", '" + escapeStringForHtml(place.name) + "', '" + place.country + "')",
                image: "img/x.png"
            });
        }

        const columns = [
            { hideifSimplified: false, content: "<a href=\"https://" + configuration.hostName + "/place/" + place.id + "\"><strong style=\"color: black\">" + getPlacePrettyName(place.name) + "</strong></a>" },
            { hideifSimplified: false, content: "<a href=\"https://" + configuration.hostName + "/plan/" + getOnlyElementOrDefault(place.categories.filter(category => category.category === "COUNTRY"), "").id + "\">" + place.country + "</a>" },
            { hideifSimplified: true, content: place.dates.length === 0 ? "Nikdy" : formatBeforeDaysTimestamp(place.dates[place.dates.length - 1].start) }
        ];

        if (showButtons) {
            columns.push({ hideifSimplified: false, content: "<div class=\"utilitiesColumn\">" + buttons.map(button => "<a onclick=\"" + button.action + "\"><img src=\"" + button.image + "\"></a>").join("") + "</div>" });
        }

        return columns;
    }

    return getGeneralTable(headerRowColumns, contentRowColumnsSelector, places);
}

async function changeName(placeId) {
    const name = prompt("Zadej nové jméno místa:");
    if (name == null || name == "") {
        return;
    }

    api.updatePlaceName(placeId, name).then(alertConfirmation);
}