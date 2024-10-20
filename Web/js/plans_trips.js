async function init(isLoggedIn) {
    const candidatePlaces = await api.listCandidatePlaces();

    // Title.
    $('#title').html(getTitle(isLoggedIn, candidatePlaces));

    // Map.
    initializeMap("map", candidatePlaces);

    // Timezone.
    $('#timezone').html(getTimezoneComponent());

    // Utilities.
    $('#utilities').html(getFooter(isLoggedIn));

    // Main.
    $('#main').html(getContentComponent(await api.listCandidateTrips(), isLoggedIn));
}

function getContentComponent(trips, showButtons) {
    const headerRowColumns = [
        { hideifSimplified: false, content: "Název"},
        { hideifSimplified: false, content: "Dnů"}
    ];
    
    if (showButtons) {
        headerRowColumns.push({ hideifSimplified: false, content: "" });
    }

    const contentRowColumnsSelector = trip => {
        const buttons = [
            { 
                action: "removeTripCandidate('" + trip.name + "', " + trip.id + ")",
                image: "img/x.png"
            }
        ];

        const columns = [
            { hideifSimplified: false, content: "<a href=\"https://" + configuration.hostName + "/plan/trip/" + trip.id + "\"><strong style=\"color: black\">" + trip.name + "</strong></a>" },
            { hideifSimplified: false, content: trip.days.total }
        ]; 

        if (showButtons) {
            columns.push({ hideifSimplified: false, content: "<div class=\"utilitiesColumn\">" + buttons.map(button => "<a onclick=\"" + button.action + "\"><img src=\"" + button.image + "\"></a>").join("") + "</div>" });
        }

        return columns;
    }

    return getGeneralTable(headerRowColumns, contentRowColumnsSelector, trips);
}

function getTitle(isLoggedIn, places) {
    return "<img src=\"img/icon.jpg\"><br>" + (isLoggedIn ? "<a onclick=\"addPlaceCandidate()\">Plán</a>" : "Plán") + "<br>" + getCountries(places).map(getFlagImage).join(" ");
}

async function removeTripCandidate(tripName, tripId) {
    if (confirm("Skutečně chceš odstranit výlet " + tripName + "?")) {
        api.removeTrip(tripId).done(alertConfirmation);
    }
}