export function formatDuration(value, includeSeconds = false) {
    const h = Math.floor(value / 3600)
    const m = Math.floor((value % 3600) / 60)
    const s = Math.round(value % 60)

    return [
        h > 0 && format(h, ["hodina", "hodiny", "hodin"]),
        m > 0 && format(m, ["minuta", "minuty", "minut"]),
        (s > 0 || (h === 0 && m === 0)) && includeSeconds && format(s, ["sekunda", "sekundy", "sekund"])
    ].filter(Boolean).join(" ") || "0 hodin"
}

export function formatEvents(value) {
    return format(value, ["událost", "události", "událostí"])
}

export function formatKilometers(value) {
    return format(value, ["kilometr", "kilometry", "kilometrů"])
}

export function formatPhotos(value) {
    return format(value, ["fotka", "fotky", "fotek"])
}

export function formatCountries(value) {
    return format(value, ["stát", "státy", "států"])
}

export function formatPlaces(value) {
    return format(value, ["místo", "místa", "míst"])
}

export function formatNextPlaces(value) {
    return ((value >= 1 && value <= 4) ? "další" : "dalších") + " " + formatPlaces(value)
}

export function formatDays(value) {
    return format(value, ["den", "dny", "dnů"])
}

export function formatFlights(value) {
    return format(value, ["let", "lety", "letů"])
}

export function formatSteps(value) {
    return format(value, ["krok", "kroky", "kroků"])
}

export function formatVisits(value) {
    return format(value, ["návětěva", "návštěvy", "návštěv"])
}

export function formatAirports(value) {
    return format(value, ["letiště", "letiště", "letišť"])
}

export function formatNights(value) {
    return format(value, ["noc", "noci", "nocí"])
}

export function formatBeforeDays(value) {
    return `Před ${format(Math.floor((Date.now() / 1000 - value) / 86400), ["dnem", "dny", "dny"])}`
}

export function formatMainCurrency(value, mainCurrency) {
    return `${value} ${mainCurrency}`
}

export function formatStatisticsUnit(unit, value, mainCurrency) {
    const statisticsUnits = {
        "KILOMETERS": formatKilometers,
        "PHOTOS": formatPhotos,
        "DURATION": formatDuration,
        "COUNTRIES": formatCountries,
        "PLACES": formatPlaces,
        "MAIN_CURRENCY": v => formatMainCurrency(v, mainCurrency),
        "DAYS": formatDays,
        "FLIGHTS": formatFlights,
        "STEPS": formatSteps,
        "BEFORE_DAYS_TIMESTAMP": formatBeforeDays,
        "VISITS": formatVisits,
        "AIRPORTS": formatAirports,
        "NIGHTS": formatNights
    }

    return statisticsUnits[unit] ? statisticsUnits[unit](value) : unit
}

function format(value, forms) {
    return `${value} ${value === 1 ? forms[0] : (value >= 2 && value <= 4) ? forms[1] : forms[2]}`
}