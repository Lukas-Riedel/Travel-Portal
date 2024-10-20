async function init(isLoggedIn) {
    const events = await api.listTimeTrackingEvents();
    
    // Content.
    $('#tracking').html([
        getContentComponent((await api.listTimeTrackingEvents("OVERTIME")), isLoggedIn, true),
        getContentComponent((await api.listTimeTrackingEvents("VACATION")), isLoggedIn),
        getContentComponent((await api.listTimeTrackingEvents("SELFCARE")), isLoggedIn),
        getContentComponent((await api.listTimeTrackingEvents("TENURE")), isLoggedIn)
    ].join("<br>"));

    // Footer.
    $('#footer').html(getFooter(isLoggedIn, [
        "<a onclick=\"createOvertimeStatement()\">Vygenerovat přehled absence</a>",
        "<a onclick=\"addTimeTrackingEvent('TENURE', -1)\">Využít tenure day</a>",
        "<a onclick=\"addTimeTrackingEvent('SELFCARE', -1)\">Využít sick day</a>",
        "<a onclick=\"addTimeTrackingEvent('VACATION', -1)\">Využít dovolenou</a>",
        "<a onclick=\"addTimeTrackingEvent('OVERTIME', -1)\">Využít přesčas</a>",
        "<a onclick=\"addTimeTrackingEvent('OVERTIME', 1)\">Přidat přesčas</a>"
    ]));
}

function getContentComponent(events, showButtons, logCsv = false) {
    const headerRowColumns = [
        { hideifSimplified: false, content: "Typ" },
        { hideifSimplified: false, content: "Datum" },
        { hideifSimplified: false, content: "Hodiny" },
        { hideifSimplified: true, content: "Popis" },
        { hideifSimplified: false, content: "Mezisoučet" }
    ];

    if (showButtons) {
        headerRowColumns.push({ hideifSimplified: false, content: "" });
    }

    const formattedBalanceTypes = [];
    const formatBalance = (type, balance) => {
        if (formattedBalanceTypes.indexOf(type) === -1) {
            formattedBalanceTypes.push(type);
            return "<strong>" + formatHoursCount(balance) + (balance === 0 ? "" :  " (" + formatDaysCount((balance / (8 * configuration.currentFte)).toFixed(2), 2) + ")") + "</strong>";
        }
        return formatHoursCount(balance) + (balance === 0 ? "" :  " (" + formatDaysCount(balance / (8 * configuration.currentFte), 2) + ")");
    }

    const contentRowColumnsSelector = event => {
        const buttons = [
            { 
                action: "removeTimeTrackingEvent(" + event.id + ")",
                image: "img/x.png"
            }
        ];

        const columns = [
            { hideifSimplified: false,  content: formatEventType(event.type) },
            { hideifSimplified: false, content: "<strong>" + getDateString(event.timestamp, true) + "</strong>" },
            { hideifSimplified: false, content: formatHours(event.hours.toFixed(2)) },
            { hideifSimplified: true, content: event.description },
            { hideifSimplified: false,  content: formatBalance(event.type, event.balance) }
        ];

        if (showButtons) {
            columns.push({ hideifSimplified: false, content: "<div class=\"utilitiesColumn\">" + buttons.map(button => "<a onclick=\"" + button.action + "\"><img src=\"" + button.image + "\"></a>").join("") + "</div>" });
        }

        return columns;
    }

    if (logCsv) {
        console.log("Date;Hours;Description\n" + reversed(events).map(event => getDateString(event.timestamp, true) + ";" + event.hours.toFixed(2) + ";" + event.description).join("\n"));
    }

    return getGeneralTable(headerRowColumns, contentRowColumnsSelector, events);
}

function addTimeTrackingEvent(type, factor) {
    const date = prompt("Zadej datum (ve formátu DD.MM.YYYY):", getDateString(now, true));
    if (date == null || date == "") {
        return;
    }

    const hours = prompt("Zadej počet hodin:", type === 'OVERTIME' && factor > 0 ? configuration.expectedOvertimeHoursPerDay : configuration.currentFte * 8);
    if (hours == null || hours == "") {
        return;
    }
    
    const description = factor > 0 ? prompt("Zadej popis:") : "Balance usage";
    if (description == null) {
        return;
    }

    api.createTimeTrackingEvent(type, factor * hours, description, date).done(reload);
}

async function createOvertimeStatement() {
    const trip = getFirstElement((await api.listTrips()).filter(trip => trip.end > now && !isDayTrips(trip)));

    const events = await api.listTimeTrackingEvents();
    const overtimeEvents = reversed(events.filter(event => event.type === 'OVERTIME'));
    const stillAvailableVacationHours = events.filter(event => event.type === 'VACATION')[0].balance
        + events.filter(event => event.type === 'SELFCARE')[0].balance
        + events.filter(event => event.type === 'TENURE')[0].balance;

    const fteHoursPerDay = 8 * configuration.currentFte;

    let hours = Math.min(trip.days.working * fteHoursPerDay, fteHoursPerDay * Math.floor(overtimeEvents[overtimeEvents.length - 1].balance / fteHoursPerDay));
    hours = Number(prompt("Zadej počet hodin přesčasů, kolik chceš vybrat:", hours));
    if (hours > overtimeEvents[overtimeEvents.length - 1].balance) {
        hours = overtimeEvents[overtimeEvents.length - 1].balance;
    }

    const positiveStatement = [];
    for (let i = 0; i < overtimeEvents.length; ++i) {
        if (overtimeEvents[i].hours > 0) {
            positiveStatement.push(overtimeEvents[i]);
        }
        else {
            let subtracted = (-1) * overtimeEvents[i].hours;
            while (subtracted > 0) {
                const removed = positiveStatement.shift(); ;
                subtracted -= removed.hours;
                subtracted = Math.round(subtracted * 100) / 100;
                if (subtracted < 0) { 
                    removed.hours = (-1) * subtracted;
                    positiveStatement.unshift(removed);
                    subtracted = 0;
                }
            }
        }
    }

    let sum = 0;
    const finalStatement = [];
    for (let i = 0; sum < hours; ++i) {
        if (sum + positiveStatement[i].hours > hours) {
            positiveStatement[i].hours = hours - sum;
        }
        finalStatement.push(positiveStatement[i]);
        sum += positiveStatement[i].hours;
    }

    const overviewTable = new JSAsciiTable([[ "From", "To", "Absence", "Overtime", "Vacation", "Remaining vacation" ], [ getDateString(trip.start), getDateString(trip.end), (trip.days.working * fteHoursPerDay).toFixed(2) + " hours", hours.toFixed(2) + " hours", (trip.days.working * fteHoursPerDay - hours).toFixed(2) + " hours", (stillAvailableVacationHours - trip.days.working * fteHoursPerDay + hours).toFixed(2) + " hours" ] ], { title: "Work absence overview", summary: false });
    let ascii = overviewTable.render();

    const overtimeTable = new JSAsciiTable([[ "Date", "Hours", "Description" ]].concat(finalStatement.map(entry => [ getDayOfWeek(new Date(entry.timestamp * 1000)) + " " + getDateString(entry.timestamp, true), entry.hours.toFixed(2), entry.description ])).concat([[ "Total", Number(hours).toFixed(2), "" ]]), { title: "Overtime hours statement", summary: true });   
    ascii += "\n\n" + overtimeTable.render();

    console.log(ascii);
    await navigator.clipboard.writeText(ascii);
    
    alertConfirmation();
}

function getDayOfWeek(date) {
    switch (date.getDay()) { case 0: return "Su"; case 1: return "Mo"; case 2: return "Tu"; case 3: return "We"; case 4: return "Th"; case 5: return "Fr"; case 6: return "Sa"; }
}

function removeTimeTrackingEvent(id) {
    if (confirm("Skutečně chceš odstranit vybranou událost?")) {
        api.removeTimeTrackingEvent(id).done(reload);
    }
}

function formatHours(hours) {
    if (hours < 0) {
        return "<strong style=\"color: red;\">" + formatHoursCount(hours) + "</strong>";
    }
    return "<strong style=\"color: green;\">+" + formatHoursCount(hours) + "</strong>";
}

function formatEventType(type) {
    if (type === "VACATION") {
        return "<img src=\"img/vacation.png\">";
    }
    if (type === "SELFCARE") {
        return "<img src=\"img/selfcare.png\">";
    }
    if (type === "TENURE") {
        return "<img src=\"img/tenure.png\">";
    }
    if (type === "OVERTIME") {
        return "<img src=\"img/overtime.png\">";
    }
    return type;
}