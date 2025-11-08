import { useEffect, useRef, useState } from "react"
import { formatStatisticsUnit } from "../utils/formatters.js"
import { useConfiguration } from "../contexts/ConfigContext"
import { decapitalize } from "../utils/helpers.js"
import { TailSpin } from "react-loader-spinner"

const loadingStatisticsCount = 20
const maxStatisticsValuesCount = 5

// TODO: This is duplicated in StatisticsCard
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

export default function StatisticsPanel({ statistics }) {
    const { configuration } = useConfiguration()

    const containerRef = useRef(null)
    const isDraggingRef = useRef(false)
    const dragStartX = useRef(0)
    const scrollStartX = useRef(0)
    const animationFrame = useRef(null)
    const [shuffledStatistics, setShuffledStatistics] = useState(() => statistics && [...statistics].sort(() => Math.random() - 0.5))

    useEffect(() => {
        if (statistics) {
            setShuffledStatistics([...statistics].sort(() => Math.random() - 0.5))
        }
    }, [statistics])

    useEffect(() => {
        let lastTimestamp = null
        let cancelled = false

        function step(timestamp) {
            if (cancelled || !containerRef.current) {
                return
            }

            if (!lastTimestamp) {
                lastTimestamp = timestamp
            }

            const delta = timestamp - lastTimestamp
            lastTimestamp = timestamp

            if (!isDraggingRef.current) {
                containerRef.current.scrollLeft += 0.7 * (delta / 16)

                const halfScrollWidth = containerRef.current.scrollWidth / 2
                if (containerRef.current.scrollLeft >= halfScrollWidth) {
                    containerRef.current.scrollLeft -= halfScrollWidth
                }
            }

            animationFrame.current = requestAnimationFrame(step)
        }

        animationFrame.current = requestAnimationFrame(step)
        return () => {
            cancelled = true
            if (animationFrame.current) {
                cancelAnimationFrame(animationFrame.current)
            }
        }
    }, [])

    function onWindowPointerMove(e) {
        if (!isDraggingRef.current) {
            return
        }

        if (!containerRef.current) {
            return
        }

        const dx = dragStartX.current - e.clientX
        containerRef.current.scrollLeft = scrollStartX.current + dx

        const halfScrollWidth = containerRef.current.scrollWidth / 2
        if (containerRef.current.scrollLeft <= 0) {
            containerRef.current.scrollLeft += halfScrollWidth
            scrollStartX.current += halfScrollWidth
            dragStartX.current = e.clientX
        }
        else if (containerRef.current.scrollLeft >= halfScrollWidth) {
            containerRef.current.scrollLeft -= halfScrollWidth
            scrollStartX.current -= halfScrollWidth
            dragStartX.current = e.clientX
        }
    }

    function onWindowPointerUp(e) {
        isDraggingRef.current = false

        window.removeEventListener("pointermove", onWindowPointerMove)
        window.removeEventListener("pointerup", onWindowPointerUp)
        window.removeEventListener("pointercancel", onWindowPointerUp)
    }

    function onPointerDown(e) {
        if (!containerRef.current) {
            return
        }

        isDraggingRef.current = true

        dragStartX.current = e.clientX
        scrollStartX.current = containerRef.current.scrollLeft

        window.addEventListener("pointermove", onWindowPointerMove)
        window.addEventListener("pointerup", onWindowPointerUp)
        window.addEventListener("pointercancel", onWindowPointerUp)

        e.preventDefault()
    }

    const doubledStats = shuffledStatistics && [...shuffledStatistics, ...shuffledStatistics]
    return (!doubledStats || doubledStats.length > 0) && (
        <div
            className="overflow-hidden py-2 my-2 relative bg-white"
            ref={containerRef}
            onPointerDown={onPointerDown}
            style={{ touchAction: "none" }}>
            <div className="flex gap-4 px-2 items-stretch">
                {(doubledStats ?? Array.from({ length: loadingStatisticsCount })).map((stat, index) => (
                    <div
                        key={index}
                        className="flex flex-col bg-white text-black px-6 py-3 rounded-xl min-w-[130px] text-center flex-shrink-0 shadow-md select-none border border-gray-200">
                        {doubledStats ? (
                            <>
                                <div className="text-sm mb-1.5 tracking-wide font-medium">
                                    {statisticsNames[stat?.name] ?? stat?.name}
                                </div>
                                <div className="flex-grow flex items-center justify-center">
                                    {Array.isArray(stat?.value) ? (
                                        <ol className="list-decimal list-inside space-y-1 text-xs text-gray-700">
                                            {stat.value.slice(0, maxStatisticsValuesCount).map((item, index) => (
                                                <li key={index}>
                                                    <span>{item.key}</span>{" "}
                                                    <span className="text-gray-400">
                                                        ({decapitalize(formatStatisticsUnit(stat?.unit, item?.value, configuration?.expensify?.mainCurrency ?? ""))})
                                                    </span>
                                                </li>
                                            ))}
                                        </ol>
                                    ) : (
                                        <div className="text-lg">
                                            {formatStatisticsUnit(stat?.unit, stat?.value, configuration?.expensify?.mainCurrency ?? "")}
                                        </div>
                                    )}
                                </div>
                            </>
                        ) : (
                            <div className="flex-grow flex items-center justify-center">
                                <div className="items-center justify-center flex h-[120px]">
                                    <TailSpin
                                        color="black"
                                        height={30}
                                        width={30} />
                                </div>
                            </div>
                        )}
                    </div>
                ))}
            </div>
        </div>
    )
}
