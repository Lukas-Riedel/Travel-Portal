import LoadingCard from "./LoadingCard"
import { decapitalize } from "../utils/helpers"
import { formatStatisticsUnit } from "../utils/formatters"
import { useConfiguration } from "../contexts/ConfigContext"
import { ResponsiveContainer, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, PieChart, Pie, Cell } from "recharts"
import { getCurrentYear } from "../utils/timeUtils.ts"

// TODO: This is duplicated in StatisticsPanel
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

const chartTypes = {
    "VISITED_PLACES_PER_CATEGORY": StandingStatisticsPieChart,
    "VISITED_PLACES_PER_COUNTRY": StandingStatisticsPieChart,
    "VISITED_PLACES_PER_CONTINENT": StandingStatisticsPieChart,
    "TOTAL_TRAVEL_DAYS_PER_CONTINENT": StandingStatisticsPieChart,
    "TOTAL_TRAVEL_DAYS_PER_COUNTRY": StandingStatisticsPieChart,
    "MOST_PHOTOS_PER_CATEGORY": StandingStatisticsPieChart,
    "MOST_PHOTOS_PER_COUNTRY": StandingStatisticsPieChart,
    "MOST_USED_AIRPORTS": StandingStatisticsPieChart,
    "MOST_USED_AIRCRAFTS": StandingStatisticsPieChart,
    "MOST_USED_AIRLINES": StandingStatisticsPieChart
}

export default function StatisticsCard({ statistics, years }) {
    const { configuration } = useConfiguration()

    const StandingStatisticsChart = chartTypes[statistics?.name] || StandingStatisticsBarChart

    return statistics ? (
        <div className="bg-white rounded-xl shadow-md max-w-xl mx-auto p-3 w-full space-y-1">
            <div className="text-lg text-center font-semibold mb-4">
                {statisticsNames[statistics.name] || statistics.name}
            </div>
            <div className="flex-grow flex items-center justify-center">
                {Array.isArray(statistics.value) ? (
                    <div className="w-full">
                        <ol className="list-decimal list-inside space-y-1 text-xs text-gray-700 text-center">
                            {statistics.value.map((item, index) => (
                                <li key={index}>
                                    <span>{item.key}</span>{" "}
                                    <span className="text-gray-400">
                                        ({decapitalize(formatStatisticsUnit(statistics.unit, item?.value, configuration?.expensify?.mainCurrency ?? ""))})
                                    </span>
                                </li>
                            ))}
                        </ol>
                        <div>
                            <StandingStatisticsChart
                                values={statistics.value.map(value => ({ name: value.key, value: value.value }))}
                                unit={statistics.unit} />
                        </div>
                    </div>
                ) : (
                    <div className="w-full">
                        <div className="text-center text-lg">
                            {formatStatisticsUnit(statistics.unit, statistics.value, configuration?.expensify?.mainCurrency ?? "")}
                        </div>
                        {years && (
                            <div>
                                <StandingStatisticsChart
                                    values={years.filter(year => year.id <= getCurrentYear()).map(year => ({ name: year.id, value: year.statistics?.find(stat => stat.name === statistics.name)?.value })).filter(year => year.value)}
                                    unit={statistics.unit} />
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    ) : (
        <LoadingCard />
    )
}

function StatisticsContainer({ children }) {
    return (
        <div className="w-full h-[300px] p-4">
            <ResponsiveContainer
                width="100%"
                height="100%">
                {children}
            </ResponsiveContainer>
        </div>
    )
}

function StandingStatisticsBarChart({ values, unit }) {
    const { configuration } = useConfiguration()

    return (
        <StatisticsContainer>
            <BarChart data={values}>
                <XAxis dataKey="name" />
                <YAxis
                    tickFormatter={value => formatStatisticsUnit(unit, value, configuration?.expensify?.mainCurrency ?? "")}
                    padding={{ top: 30, bottom: 0 }} />
                <Tooltip content={<CustomTooltip formatValue={value => formatStatisticsUnit(unit, value, configuration?.expensify?.mainCurrency ?? "")} />} />
                <Bar
                    dataKey="value"
                    fill="#33ccff"
                    radius={[6, 6, 0, 0]} />
            </BarChart>
        </StatisticsContainer>
    )
}

function StandingStatisticsPieChart({ values, unit }) {
    const { configuration } = useConfiguration()

    const getRandomColor = index => {
        const hue = (index * 360) / values.length
        return `hsl(${hue}, 65%, 55%)`
    }

    return (
        <StatisticsContainer>
            <PieChart>
                <Pie
                    data={values}
                    dataKey="value"
                    nameKey="name"
                    cx="50%"
                    cy="50%"
                    outerRadius={100}
                    label={({ name }) => name}>
                    {values.map((_, index) => (
                        <Cell
                            key={index}
                            fill={getRandomColor(index)} />
                    ))}
                </Pie>
                <Tooltip content={<CustomTooltip formatValue={value => formatStatisticsUnit(unit, value, configuration?.expensify?.mainCurrency ?? "")} />} />
            </PieChart>
        </StatisticsContainer>
    )
}

function CustomTooltip({ active, payload, formatValue }) {
    return active && payload && (
        <div className="bg-white/90 bg-white border shadow px-3 py-2 rounded">
            <div className="font-semibold">
                {payload[0].payload.name}
            </div>
            <div>
                {formatValue(payload[0].payload.value)}
            </div>
        </div>
    )
}