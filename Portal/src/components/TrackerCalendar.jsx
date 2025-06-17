import { useMemo, useState } from "react"
import { ChevronLeft, ChevronRight, Home, PlaneTakeoff, PlaneLanding, Clock, Shield, ClockPlus, Plane, Palmtree, Pill, Plus } from "lucide-react"
import { format, getDaysInMonth, startOfMonth, getDay, eachDayOfInterval, startOfDay, endOfDay, addDays, startOfWeek } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import cs from "date-fns/locale/cs"
import { useConfiguration } from "../contexts/ConfigContext"
import { fromUnixTime } from "date-fns"
import Tooltip from "./Tooltip"
import { formatDuration } from "../utils/formatters"
import { useAuth } from "../contexts/AuthContext"

export default function TrackerCalendar({ trips, isPublicHoliday, overtimeEvents, plannedWorkEvents, vacationEvents, selfcareEvents, tenureEvents }) {
    const { isAdmin } = useAuth()
    const configuration = useConfiguration()

    const now = new Date()
    const timezone = useMemo(() => configuration?.homeLocation?.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || "UTC", [configuration])
    const standardWorkingHoursPerWorkingDay = useMemo(() => 8 * configuration?.currentFte || 8, [configuration])

    const [date, setDate] = useState(() => startOfMonth(now))

    const earliestAllowedDate = new Date(now.getFullYear(), now.getMonth() - 1, 1)

    const latestAllowedDate = useMemo(() => {
        const endOfYear = new Date(now.getFullYear(), 11, 31)
        const last = trips?.at(-1)
        const base = last && toZonedTime(fromUnixTime(last.end - 1)) > now ? toZonedTime(fromUnixTime(last.end - 1), timezone) : new Date(new Date(now).getTime() + 60 * 86400 * 1000)
        return base > endOfYear ? endOfYear : base
    }, [trips, now, timezone, configuration])

    const filteredTrips = useMemo(() => trips?.filter(({ start, end }) => toZonedTime(fromUnixTime(end - 1), timezone) > earliestAllowedDate
        && toZonedTime(fromUnixTime(start), timezone) < latestAllowedDate) ?? [], [trips, earliestAllowedDate, latestAllowedDate, timezone])

    const isPreviousMonthDisabled = useMemo(() => new Date(date.getFullYear(), date.getMonth() - 1, 1) < earliestAllowedDate, [date, earliestAllowedDate])

    const getFlight = day => {
        const targetDay = startOfDay(day).getTime()

        for (const { flights } of trips || []) {
            if (!flights || flights.length === 0) {
                continue
            }

            const firstFlight = flights[0]
            if (firstFlight?.start) {
                const flightDate = startOfDay(toZonedTime(fromUnixTime(firstFlight.start), timezone)).getTime()
                if (flightDate === targetDay) {
                    return {
                        flight: firstFlight.flight,
                        from: firstFlight.from.name,
                        to: firstFlight.to.name,
                        time: firstFlight.start,
                        icon: PlaneTakeoff
                    }
                }
            }

            const lastFlight = flights.at(-1)
            if (lastFlight?.end) {
                const flightDate = startOfDay(toZonedTime(fromUnixTime(lastFlight.end), timezone)).getTime()
                if (flightDate === targetDay) {
                    return {
                        flight: lastFlight.flight,
                        from: lastFlight.from.name,
                        to: lastFlight.to.name,
                        time: lastFlight.end,
                        icon: PlaneLanding
                    }
                }
            }
        }

        return null
    }

    const getEvents = (day, events, hoursFilter) => {
        const targetDay = startOfDay(day).getTime()
        return (events ?? [])
            .filter(event => hoursFilter(event.hours) && startOfDay(toZonedTime(fromUnixTime(event.timestamp), timezone)).getTime() === targetDay)
            .map(event => ({
                description: event.description,
                hours: event.hours,
                balance: event.balance
            }))
    }

    const getPositiveOvertime = day => getEvents(day, overtimeEvents, hours => hours > 0)
    const getNegativeOvertime = day => getEvents(day, overtimeEvents, hours => hours < 0)
    const getVacation = day => getEvents(day, vacationEvents, hours => hours < 0)
    const getSelfcare = day => getEvents(day, selfcareEvents, hours => hours < 0)
    const getTenure = day => getEvents(day, tenureEvents, hours => hours < 0)
    const getPlannedWork = day => getEvents(day, plannedWorkEvents, _ => true)

    const sumEventHours = events => events.map(e => e.hours).reduce((a, b) => a + b, 0)

    const formatTimestamp = timestamp => format(toZonedTime(fromUnixTime(timestamp), timezone), "HH:mm")

    const changeMonth = offset => setDate(previous => startOfMonth(new Date(previous.getFullYear(), previous.getMonth() + offset, 1)))
    const goToToday = () => setDate(startOfMonth(now))

    const isInTrip = day => filteredTrips.some(({ start, end }) => start * 1000 <= endOfDay(day).getTime() && end * 1000 > startOfDay(day).getTime())

    const daysOfWeek = Array.from({ length: 7 }, (_, i) => format(addDays(startOfWeek(new Date(1970, 0, 1), { weekStartsOn: 1 }), i), "EE", { locale: cs }))

    const days = useMemo(() => {
        return eachDayOfInterval({
            start: startOfDay(earliestAllowedDate),
            end: startOfDay(latestAllowedDate)
        }).map(day => {
            const isFreeDay = day.getDay() === 0 || day.getDay() === 6 || isPublicHoliday(day);
            const standardWorkingHours = isFreeDay ? 0 : standardWorkingHoursPerWorkingDay;
            const positiveOvertime = getPositiveOvertime(day);
            const negativeOvertime = getNegativeOvertime(day);
            const vacation = getVacation(day);
            const selfcare = getSelfcare(day)
            const tenure = getTenure(day)
            const plannedWork = getPlannedWork(day)

            return {
                day,
                flight: getFlight(day),
                positiveOvertime,
                negativeOvertime,
                vacation,
                selfcare,
                tenure,
                plannedWork,
                isInTrip: isInTrip(day),
                standardWorkingHours,
                actualWorkingHours: (day > now ? 0 : standardWorkingHours)
                    + sumEventHours(positiveOvertime)
                    + sumEventHours(negativeOvertime)
                    + sumEventHours(vacation)
                    + sumEventHours(selfcare)
                    + sumEventHours(tenure),
                expectedWorkingHours: (isFreeDay || isInTrip(day) ? 0 : 8)
                    + sumEventHours(plannedWork)
            };
        });
    }, [earliestAllowedDate, latestAllowedDate, configuration, trips]);







    function getWeekday(date) {
        // Monday as 0
        const d = getDay(date)
        return d === 0 ? 6 : d - 1
    }

    const year = date.getFullYear()
    const month = date.getMonth()
    const daysInMonth = getDaysInMonth(date)

    // Build calendar with null padding for Monday start
    const calendarDays = []
    const firstDayWeekday = getWeekday(date)
    for (let i = 0; i < firstDayWeekday; i++) calendarDays.push(null)
    for (let d = 1; d <= daysInMonth; d++) {
        const dayDate = new Date(year, month, d)
        calendarDays.push(dayDate)
    }

    // Map flights by day key (start of day in timezone, number timestamp)
    const flightsByDay = {}

    filteredTrips.forEach(({ flights }) => {
        if (!flights || flights.length === 0) return
        const firstFlight = flights[0]
        const lastFlight = flights[flights.length - 1]

        if (firstFlight?.start) {
            const flightDate = toZonedTime(fromUnixTime(firstFlight.start), timezone)
            flightDate.setHours(0, 0, 0, 0)
            const dayKey = flightDate.getTime()
            if (!flightsByDay[dayKey]) flightsByDay[dayKey] = []
            if (!flightsByDay[dayKey].some((f) => f === firstFlight)) {
                flightsByDay[dayKey].push(firstFlight)
            }
        }

        if (lastFlight?.end && flights.length > 1) {
            const flightDate = toZonedTime(fromUnixTime(lastFlight.end), timezone)
            flightDate.setHours(0, 0, 0, 0)
            const dayKey = flightDate.getTime()
            if (!flightsByDay[dayKey]) flightsByDay[dayKey] = []
            if (!flightsByDay[dayKey].some((f) => f === lastFlight)) {
                flightsByDay[dayKey].push(lastFlight)
            }
        }
    })

    const renderFlight = flight => {
        const Icon = flight.icon
        return (
            <li key="flight" className="relative group">
                <div className="flex items-center space-x-1 text-white text-xs leading-tight truncate">
                    <Icon size={16} />
                    <span className="font-medium">
                        {formatTimestamp(flight.time)}
                    </span>
                    <span className="whitespace-nowrap  text-ellipsis">
                        <a
                            href={`https://www.flightradar24.com/data/flights/${flight.flight}`}
                            className="hover:underline hover:text-gray-300 transition-colors duration-200">
                            {flight.flight}
                        </a>
                    </span>
                </div>
                <Tooltip>
                    <Plane size={16} />
                    {`${flight.from} → ${flight.to}`}
                </Tooltip>
            </li>
        )
    }

    const renderAbsence = (key, events, icon) => {
        const Icon = icon
        return (
            <li key={key} className="relative group">
                <div className="flex items-center space-x-1 text-white text-xs leading-tight truncate">
                    <Icon size={16} />
                    <span className="font-medium truncate">
                        {formatDuration(events.map(e => (-1) * e.hours).reduce((acc, val) => acc + val, 0) * 3600)}
                    </span>
                    <Tooltip>
                        <Icon size={16} />
                        {`Zbývá ${formatDuration(Math.min(...events.map(e => e.balance)) * 3600)}`}
                    </Tooltip>
                </div>
            </li>
        )
    }

    const renderButtons = day => (
        <li key="buttons" className="relative group">
            <div className="flex items-center space-x-1 text-white text-xs leading-tight truncate">
                <Plus size={16} />
                <a className="font-medium truncate">
                    Zalogovat čas
                </a>
            </div>
        </li>
    )
    
    return (
        <div className="w-full p-4 border rounded-md shadow-md bg-white text-black my-4">
            <div className="flex items-center justify-between mb-4">
                <button
                    onClick={() => {
                        if (!isPreviousMonthDisabled) {
                            changeMonth(-1)
                        }
                    }}
                    className={`p-2 rounded ${!isPreviousMonthDisabled ? "hover:bg-gray-200" : "opacity-50 cursor-not-allowed"}`}
                    aria-label="Previous Month"
                    type="button">
                    <ChevronLeft className="w-5 h-5" />
                </button>

                <h2 className="text-lg font-semibold">
                    {format(date, "LLLL yyyy", { locale: cs })}
                </h2>

                <div className="flex items-center space-x-2">
                    <button
                        onClick={goToToday}
                        className="p-2 rounded hover:bg-gray-200"
                        aria-label="Go to Today"
                        type="button"
                    >
                        <Home className="w-5 h-5" />
                    </button>

                    <button
                        onClick={() => changeMonth(1)}
                        className="p-2 rounded hover:bg-gray-200"
                        aria-label="Next Month"
                        type="button"
                    >
                        <ChevronRight className="w-5 h-5" />
                    </button>
                </div>
            </div>

            <table className="w-full table-fixed border-collapse text-center">
                <thead>
                    <tr>
                        {daysOfWeek.map((day) => (
                            <th
                                key={day}
                                className="border p-1 text-sm font-medium text-gray-600 select-none"
                            >
                                {day}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {[...Array(Math.ceil(calendarDays.length / 7))].map((_, weekIndex) => (
                        <tr key={weekIndex}>
                            {calendarDays
                                .slice(weekIndex * 7, weekIndex * 7 + 7)
                                .map((dayDate, i) => {
                                    if (!dayDate) {
                                        return <td key={i} className="border p-2 h-24"></td>
                                    }

                                    const foundDay = days?.find(d => d.day.getTime() === dayDate.getTime())

                                    let bgColorClass = "bg-white"
                                    let hoverClass = "hover:bg-white"
                                    if (foundDay) {
                                        if (foundDay.isInTrip) {
                                            bgColorClass = "bg-red-600 text-white"
                                            hoverClass = "hover:bg-red-700"
                                        }
                                        if (foundDay.standardWorkingHours === 0) {
                                            bgColorClass = "bg-amber-600 text-white"
                                            hoverClass = "hover:bg-amber-700"
                                        }
                                        if (!foundDay.isInTrip && foundDay.standardWorkingHours > 0) {
                                            bgColorClass = "bg-green-700 text-white"
                                            hoverClass = "hover:bg-green-800"
                                        }
                                        if (foundDay.isInTrip && (foundDay.actualWorkingHours > 0 || foundDay.expectedWorkingHours > 0)) {
                                            bgColorClass = "bg-cyan-600 text-white"
                                            hoverClass = "hover:bg-cyan-700"
                                        }
                                    }

                                    return (
                                        <td
                                            key={i}
                                            className={`border p-2 align-top relative h-32 cursor-pointer select-none ${bgColorClass} ${hoverClass}`}>
                                            <span className="absolute top-1 right-2 text-xs font-semibold">
                                                {dayDate.getDate()}
                                            </span>
                                            <div className="mt-5 space-y-0.5 text-xs leading-tight">
                                                <div className="space-y-0.5 text-xs text-white leading-tight">
                                                    {foundDay && (
                                                        <>
                                                            <div className="flex items-center space-x-1 font-medium truncate">
                                                                <Clock size={16} />
                                                                <span>
                                                                    {foundDay.day >= startOfDay(now)
                                                                        ? formatDuration(foundDay.expectedWorkingHours * 3600)
                                                                        : formatDuration(foundDay.actualWorkingHours * 3600)}
                                                                </span>
                                                            </div>
                                                            {foundDay.positiveOvertime.map((event, idx) => (
                                                                <div key={idx} className="relative group">
                                                                    <div className="flex items-start space-x-1">
                                                                        <span className="text-2xs text-ellipsis truncate">{event.description}</span>
                                                                    </div>
                                                                    <Tooltip>
                                                                        <ClockPlus size={16} />
                                                                        {`${event.description} (${formatDuration(event.hours * 3600)})`}
                                                                    </Tooltip>
                                                                </div>

                                                            ))}
                                                        </>
                                                    )}
                                                </div>

                                                {(isAdmin || foundDay) && (
                                                    <ul className="space-y-0.5 m-0 p-0 list-none absolute bottom-2 left-2 right-2 max-h-[6rem]">
                                                        {foundDay?.flight && renderFlight(foundDay.flight)}
                                                        {foundDay?.negativeOvertime?.length > 0 && renderAbsence("negativeOvertime", foundDay.negativeOvertime, ClockPlus)}
                                                        {foundDay?.vacation?.length > 0 && renderAbsence("vacation", foundDay.vacation, Palmtree)}
                                                        {foundDay?.selfcare?.length > 0 && renderAbsence("selfcare", foundDay.selfcare, Pill)}
                                                        {foundDay?.tenure?.length > 0 && renderAbsence("tenure", foundDay.tenure, Shield)}
                                                        {isAdmin && renderButtons(dayDate)}
                                                    </ul>
                                                )}

                                            </div>
                                        </td>
                                    )
                                })}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    )
}
