export function formatDuration(value, includeSeconds = false) {
    const h = Math.floor(value / 3600)
    const m = Math.floor((value % 3600) / 60)
    const s = value % 60

    return [
        h > 0 && format(h, ["hodina", "hodiny", "hodin"]),
        m > 0 && format(m, ["minuta", "minuty", "minut"]),
        (s > 0 || (h === 0 && m === 0)) && includeSeconds && format(s, ["sekunda", "sekundy", "sekund"])
    ].filter(Boolean).join(" ") || "0 hodin"
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

export function formatStatisticsName(name) {
    const statisticsNames = {
        "LEAST_RECENTLY_VISITED_PLACES": "Nejdéle nenavštívená místa",
        "TOTAL_VISITED_AIRPORTS_COUNT": "Počet navštívených letišť",
        "TOTAL_AIRBORNE_DISTANCE": "Počet nalétaných kilometrů",
        "AVERAGE_FLIGHT_DURATION": "Průměrná doba letu",
        "TOTAL_AIRBORNE_TIME": "Letový čas",
        "TOTAL_PHOTOS_COUNT": "Počet fotek",
        "TOTAL_VISITED_COUNTRIES_COUNT": "Počet navštívených zemí",
        "TOTAL_VISITED_PLACES_COUNT": "Počet navštívených míst",
        "TOTAL_EXPENSES": "Celkové výdaje",
        "AVERAGE_EXPENSES_PER_DAY": "Průměrné výdaje za den",
        "TOTAL_TRAVEL_DAYS_COUNT": "Počet cestovních dnů",
        "TOTAL_HOTEL_NIGHTS_COUNT": "Počet nocí v hotelu",
        "AVERAGE_NIGHTS_PER_HOTEL": "Průměrný počet nocí v hotelu",
        "TOTAL_FLIGHTS_COUNT": "Počet letů",
        "AVERAGE_PHOTOS_PER_ALBUM": "Průměrný počet fotek v albu",
        "AVERAGE_TRIP_LENGTH": "Průměrná délka výletu",
        "MOST_USED_AIRCRAFTS": "Nejvyužívanější letadla",
        "MOST_USED_AIRLINES": "Nejvyužívanější letecké společnosti",
        "SHORTEST_FLIGHTS": "Nejkratší lety",
        "LONGEST_FLIGHTS": "Nejdelší lety",
        "MOST_USED_AIRPORTS": "Nejvyužívanější letiště",
        "MOST_PHOTOS_PER_DAY": "Nejvíce fotek za den",
        "MOST_PHOTOS_PER_PLACE": "Nejvíce fotek pro místo",
        "MOST_PHOTOS_PER_COUNTRY": "Nejvíce fotek ve státě",
        "MOST_PHOTOS_PER_TRIP": "Výlety s nejvyšším počtem fotek",
        "MOST_PHOTOS_PER_CATEGORY": "Nejvíce fotek v oblasti",
        "MOST_USED_FLIGHTS": "Nejvyužívanější letové linky",
        "MOST_USED_AIRCRAFT_REGISTRATIONS": "Nejvyužívanější stroje",
        "FURTHEST_PLACES": "Nejvzdálenější místa",
        "FURTHEST_COUNTRIES": "Nejvzdálenější státy",
        "VISITED_PLACES_PER_COUNTRY": "Počet navštívených míst ve státě",
        "VISITED_PLACES_PER_CONTINENT": "Počet navštívených míst na kontinentu",
        "VISITED_PLACES_PER_CATEGORY": "Počet navštívených míst v oblasti",
        "LONGEST_TRIPS": "Nejdelší výlety",
        "SHORTEST_TRIPS": "Nejkratší výlety",
        "MOST_EXPENSIVE_TRIPS": "Nejdražší výlety",
        "LEAST_EXPENSIVE_TRIPS": "Nejlevnější výlety",
        "MOST_EXPENSIVE_TRIPS_PER_DAY": "Výlety s nejvyššími výdaji za den",
        "LEAST_EXPENSIVE_TRIPS_PER_DAY": "Výlety s nejnižšími výdaji za den",
        "LONGEST_HOTEL_STAYS": "Nejdelší pobyty v hotelu",
        "MOST_EXPENSIVE_HOTEL_STAYS_PER_NIGHT": "Nejdražší pobyty v hotelu na noc",
        "LEAST_EXPENSIVE_HOTEL_STAYS_PER_NIGHT": "Nejlevnější pobyty v hotelu na noc",
        "TOTAL_TRAVEL_DAYS_PER_COUNTRY": "Počet dnů ve státě",
        "TOTAL_TRAVEL_DAYS_PER_CONTINENT": "Počet dnů na kontinentu",
        "MOST_DELAYED_FLIGHTS": "Nejvíce zpožděné lety",
        "TOTAL_STEPS_COUNT": "Počet kroků",
        "AVERAGE_STEPS_PER_DAY": "Průměrný počet kroků za den",
        "TOTAL_TIME_IN_MOTION": "Čas v pohybu",
        "AVERAGE_TIME_IN_MOTION_PER_DAY": "Průměrný čas v pohybu za den",
        "MOST_AVERAGE_STEPS_PER_DAY_TRIPS": "Výlety s nejvyšším průměrným počtem kroků za den",
        "LEAST_AVERAGE_STEPS_PER_DAY_TRIPS": "Výlety s nejnižším průměrným počtem kroků za den",
        "MOST_AVERAGE_TIME_IN_MOTION_PER_DAY_TRIPS": "Výlety s nejvyšším průměrným časem v pohybu za den",
        "LEAST_AVERAGE_TIME_IN_MOTION_PER_DAY_TRIPS": "Výlety s nejnižším průměrným časem v pohybu za den",
        "MOST_STEPS_PER_DAY": "Nejvyšší počet kroků za den",
        "LEAST_STEPS_PER_DAY": "Nejnižší počet kroků za den",
        "MOST_TIME_IN_MOTION_PER_DAY": "Nejvyšší čas v pohybu za den",
        "LEAST_TIME_IN_MOTION_PER_DAY": "Nejnižší čas v pohybu za den",
        "LAST_VISIT": "Poslední návštěva",
        "MOST_VISITED_PLACES": "Nejčastěji navštěvovaná místa",
        "WESTERNMOST_PLACES": "Nejzápadnější místa",
        "EASTERNMOST_PLACES": "Nejvýchodnější místa",
        "NORTHERNMOST_PLACES": "Nejsevernější místa",
        "SOUTHERNMOST_PLACES": "Nejjižnější místa"
    }

    return statisticsNames[name] ?? name
}

function format(value, forms) {
    return `${value} ${value === 1 ? forms[0] : (value >= 2 && value <= 4) ? forms[1] : forms[2]}`
}