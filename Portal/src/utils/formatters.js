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
    return format(Math.round(value), ["kilometr", "kilometry", "kilometrů"])
}

export function formatMeters(value) {
    return format(Math.round(value), ["metr", "metry", "metrů"])
}

export function formatPhotos(value) {
    return format(value, ["fotka", "fotky", "fotek"])
}

export function formatNewProblems(value) {
    return format(value, ["nový problém", "nové problémy", "nových problémů"])
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

export function formatLatitude(val) {
    const abs = Math.abs(val);
    const d = Math.floor(abs);
    const m = Math.floor((abs - d) * 60);
    const s = Math.round((abs - d - m / 60) * 3600);
    return `${d}° ${m}' ${s}" ${val >= 0 ? "N" : "S"}`;
}

export function formatLongitude(val) {
    const abs = Math.abs(val);
    const d = Math.floor(abs);
    const m = Math.floor((abs - d) * 60);
    const s = Math.round((abs - d - m / 60) * 3600);
    return `${d}° ${m}' ${s}" ${val >= 0 ? "E" : "W"}`;
}

export function formatBeforeDays(value) {
    return format(Math.floor((Date.now() / 1000 - value) / 86400), ["dnem", "dny", "dny"])
}

export function formatBeforeMinutes(value) {
    return format(Math.floor((Date.now() / 1000 - value) / 60), ["minutou", "minutami", "minutami"])
}

export function formatBeforeHours(value) {
    return format(Math.floor((Date.now() / 1000 - value) / 3600), ["hodinou", "hodinami", "hodinami"])
}

export function formatTimeAgo(timestamp) {
    const seconds = Math.floor(Date.now() / 1000 - timestamp)
    if (seconds < 60) {
        return "několika sekundami"
    }

    const minutes = Math.floor(seconds / 60)
    if (minutes < 60) {
        return formatBeforeMinutes(timestamp)
    }

    const hours = Math.floor(minutes / 60)
    if (hours < 24) {
        return formatBeforeHours(timestamp)
    }

    return formatBeforeDays(timestamp);
}

export function formatMainCurrency(value, mainCurrency) {
    return `${value} ${mainCurrency}`
}

export function formatStatisticsUnit(unit, value, mainCurrency) {
    const statisticsUnits = {
        "kilometers": formatKilometers,
        "photos": formatPhotos,
        "duration": formatDuration,
        "countries": formatCountries,
        "places": formatPlaces,
        "mainCurrency": v => formatMainCurrency(v, mainCurrency),
        "days": formatDays,
        "flights": formatFlights,
        "steps": formatSteps,
        "beforeDaysTimestamp": v => "Před " + formatBeforeDays(v),
        "visits": formatVisits,
        "airports": formatAirports,
        "nights": formatNights,
        "latitude": formatLatitude,
        "longitude": formatLongitude
    }

    return statisticsUnits[unit] ? statisticsUnits[unit](value) : (value + " " + unit)
}

function format(value, forms) {
    return `${value} ${value === 1 ? forms[0] : (value >= 2 && value <= 4) ? forms[1] : forms[2]}`
}