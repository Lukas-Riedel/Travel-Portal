const statsNames = {"LEAST_RECENTLY_VISITED_PLACES":"Nejdéle nenavštívená místa","TOTAL_VISITED_AIRPORTS_COUNT":"Počet navštívených letišť","TOTAL_AIRBORNE_DISTANCE":"Počet nalétaných kilometrů","AVERAGE_FLIGHT_DURATION":"Průměrná doba letu","TOTAL_AIRBORNE_TIME":"Letový čas","TOTAL_PHOTOS_COUNT":"Počet fotek","TOTAL_VISITED_COUNTRIES_COUNT":"Počet navštívených zemí","TOTAL_VISITED_PLACES_COUNT":"Počet navštívených míst","TOTAL_EXPENSES":"Celkové výdaje","AVERAGE_EXPENSES_PER_DAY":"Průměrné výdaje za den","TOTAL_TRAVEL_DAYS_COUNT":"Počet cestovních dnů","TOTAL_HOTEL_NIGHTS_COUNT":"Počet nocí v hotelu","AVERAGE_NIGHTS_PER_HOTEL":"Průměrný počet nocí v hotelu","TOTAL_FLIGHTS_COUNT":"Počet letů","AVERAGE_PHOTOS_PER_ALBUM":"Průměrný počet fotek v albu","AVERAGE_TRIP_LENGTH":"Průměrná délka výletu","MOST_USED_AIRCRAFTS":"Nejvyužívanější letadla","MOST_USED_AIRLINES":"Nejvyužívanější letecké společnosti","SHORTEST_FLIGHTS":"Nejkratší lety","LONGEST_FLIGHTS":"Nejdelší lety","MOST_USED_AIRPORTS":"Nejvyužívanější letiště","MOST_PHOTOS_PER_DAY":"Nejvíce fotek za den","MOST_PHOTOS_PER_PLACE":"Nejvíce fotek pro místo","MOST_PHOTOS_PER_COUNTRY":"Nejvíce fotek ve státě","MOST_PHOTOS_PER_TRIP":"Výlety s nejvyšším počtem fotek","MOST_PHOTOS_PER_CATEGORY":"Nejvíce fotek v oblasti","MOST_USED_FLIGHTS":"Nejvyužívanější letové linky","MOST_USED_AIRCRAFT_REGISTRATIONS":"Nejvyužívanější stroje","FURTHEST_PLACES":"Nejvzdálenější místa","FURTHEST_COUNTRIES":"Nejvzdálenější státy","VISITED_PLACES_PER_COUNTRY":"Počet navštívených míst ve státě","VISITED_PLACES_PER_CATEGORY":"Počet navštívených míst v oblasti","LONGEST_TRIPS":"Nejdelší výlety","SHORTEST_TRIPS":"Nejkratší výlety","MOST_EXPENSIVE_TRIPS":"Nejdražší výlety","LEAST_EXPENSIVE_TRIPS":"Nejlevnější výlety","MOST_EXPENSIVE_TRIPS_PER_DAY":"Výlety s nejvyššími výdaji za den","LEAST_EXPENSIVE_TRIPS_PER_DAY":"Výlety s nejnižšími výdaji za den","LONGEST_HOTEL_STAYS":"Nejdelší pobyty v hotelu","MOST_EXPENSIVE_HOTEL_STAYS_PER_NIGHT":"Nejdražší pobyty v hotelu na noc","LEAST_EXPENSIVE_HOTEL_STAYS_PER_NIGHT":"Nejlevnější pobyty v hotelu na noc","DAYS_PER_COUNTRY":"Počet dnů ve státě","MOST_DELAYED_FLIGHTS":"Nejvíce zpožděné lety","TOTAL_STEPS_COUNT":"Počet kroků","AVERAGE_STEPS_PER_DAY":"Průměrný počet kroků za den","TOTAL_TIME_IN_MOTION":"Čas v pohybu","AVERAGE_TIME_IN_MOTION_PER_DAY":"Průměrný čas v pohybu za den","MOST_AVERAGE_STEPS_PER_DAY_TRIPS":"Výlety s nejvyšším průměrným počtem kroků za den","LEAST_AVERAGE_STEPS_PER_DAY_TRIPS":"Výlety s nejnižším průměrným počtem kroků za den","MOST_AVERAGE_TIME_IN_MOTION_PER_DAY_TRIPS":"Výlety s nejvyšším průměrným časem v pohybu za den","LEAST_AVERAGE_TIME_IN_MOTION_PER_DAY_TRIPS":"Výlety s nejnižším průměrným časem v pohybu za den","MOST_STEPS_PER_DAY":"Nejvyšší počet kroků za den","LEAST_STEPS_PER_DAY":"Nejnižší počet kroků za den","MOST_TIME_IN_MOTION_PER_DAY":"Nejvyšší čas v pohybu za den","LEAST_TIME_IN_MOTION_PER_DAY":"Nejnižší čas v pohybu za den","LAST_VISIT":"Poslední návštěva","MOST_VISITED_PLACES":"Nejčastěji navštěvovaná místa","WESTERNMOST_PLACES":"Nejzápadnější místa","EASTERNMOST_PLACES":"Nejvýchodnější místa","NORTHERNMOST_PLACES":"Nejsevernější místa","SOUTHERNMOST_PLACES":"Nejjižnější místa"};

function getStatsComponent(stats) {
    const headerRow = "<tr><th>Statistický údaj</th><th>Hodnota</th></tr>";

    const facts = stats.filter(stat => !Array.isArray(stat.value));
    const standings = stats.filter(stat => Array.isArray(stat.value));

    const contentFactsRows = facts.map(stat => {
        const columns = [
            statsNames[stat.name],
            resolveFormatter(stat.unit)(stat.value)
        ]

        return "<tr>" + columns.map(column => "<td>" + column + "</td>").join("") + "</tr>";
    });

    const contentStandingsRows = standings.map(stat => {
        const columns = [
            statsNames[stat.name],
            "<ol>" + stat.value.map(item => "<li>" + item.key + " (" + resolveFormatter(stat.unit)(item.value) + ")</li>").join("") + "</ol>"
        ]

        return "<tr>" + columns.map(column => "<td>" + column + "</td>").join("") + "</tr>";
    });

    return "<table>" + headerRow + contentFactsRows.join("") + contentStandingsRows.join("") + "</table>";
}

function resolveFormatter(unit) {
    switch (unit) {
        case "AIRPORTS":
            return formatAirportsCount;
        case "KILOMETERS":
            return formatKilometersCount;
        case "VISITS":
            return formatVisitsCount;
        case "FLIGHTS":
            return formatFlightsCount;
        case "NIGHTS":
            return formatNightsCount;
        case "PHOTOS":
            return formatPhotosCount;
        case "COUNTRIES":
            return formatCountriesCount;
        case "PLACES":
            return formatPlacesCount;
        case "DAYS":
            return formatDaysCount;
        case "MAIN_CURRENCY":
            return value => value + " " + configuration.mainCurrency;
        case "STEPS":
            return formatStepsCount;
        case "BEFORE_DAYS_TIMESTAMP":
            return formatBeforeDaysTimestamp;
        case "DURATION":
            return formatDuration;
        default:
            return value => value + " " + unit;
    }
}