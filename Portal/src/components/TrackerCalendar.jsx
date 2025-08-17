import { useMemo, useState } from "react"
import { ChevronLeft, ChevronRight, Home, PlaneTakeoff, PlaneLanding, Clock, Shield, ClockPlus, Plane, Palmtree, Pill, Plus, Trash2, CalendarPlus } from "lucide-react"
import { format, getDaysInMonth, startOfMonth, getDay, startOfDay, endOfDay, addDays, startOfWeek } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import cs from "date-fns/locale/cs"
import { useConfiguration } from "../contexts/ConfigContext"
import { fromUnixTime } from "date-fns"
import Tooltip from "./Tooltip"
import { formatDuration } from "../utils/formatters"
import { useAuth } from "../contexts/AuthContext"
import showConfirmToast from "./ConfirmToast"
import showFormToast from "./FormToast"
import { getEvents, isInTrip, sumEventHours } from "../utils/helpers"

export default function TrackerCalendar({ trips, isFreeDay, overtimeEvents, plannedWorkEvents, vacationEvents, selfcareEvents, tenureEvents, onEventCreated, onEventRemoved }) {
    const { isAdmin } = useAuth()
    const { configuration } = useConfiguration()

    const now = new Date()
    const timezone = useMemo(() => configuration?.homeLocation?.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || "UTC", [configuration])
    const standardWorkingHoursPerWorkingDay = useMemo(() => 8 * configuration?.timeTracking?.currentFte || 8, [configuration])
    const expectedOvertimeHoursPerDay = useMemo(() => configuration?.timeTracking?.expectedOvertimeHoursPerDay || 0, [configuration])

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

    const getPositiveOvertime = day => getEvents(day, overtimeEvents, hours => hours > 0, timezone)
    const getNegativeOvertime = day => getEvents(day, overtimeEvents, hours => hours < 0, timezone)
    const getVacation = day => getEvents(day, vacationEvents, hours => hours < 0, timezone)
    const getSelfcare = day => getEvents(day, selfcareEvents, hours => hours < 0, timezone)
    const getTenure = day => getEvents(day, tenureEvents, hours => hours < 0, timezone)
    const getPlannedWork = day => getEvents(day, plannedWorkEvents, _ => true, timezone)

    const formatTimestamp = timestamp => format(toZonedTime(fromUnixTime(timestamp), timezone), "HH:mm")
    const getWeekday = date => (getDay(date) + 6) % 7

    const changeMonth = offset => setDate(previous => startOfMonth(new Date(previous.getFullYear(), previous.getMonth() + offset, 1)))
    const goToToday = () => setDate(startOfMonth(now))

    const getDaySummary = day => {
        if (day < earliestAllowedDate || day > latestAllowedDate) {
            return null
        }

        const standardWorkingHours = isFreeDay(day) ? 0 : standardWorkingHoursPerWorkingDay
        const positiveOvertime = getPositiveOvertime(day)
        const negativeOvertime = getNegativeOvertime(day)
        const vacation = getVacation(day)
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
            isInTrip: isInTrip(filteredTrips, day),
            standardWorkingHours,
            actualWorkingHours: (day > now ? 0 : standardWorkingHours)
                + sumEventHours(positiveOvertime)
                + sumEventHours(negativeOvertime)
                + sumEventHours(vacation)
                + sumEventHours(selfcare)
                + sumEventHours(tenure),
            expectedWorkingHours: (isFreeDay(day) || isInTrip(filteredTrips, day) ? 0 : (standardWorkingHours + expectedOvertimeHoursPerDay))
                + sumEventHours(plannedWork)
        }
    }

    const daysOfWeek = Array.from({ length: 7 }, (_, i) => format(addDays(startOfWeek(new Date(1970, 0, 1), { weekStartsOn: 1 }), i), "EE", { locale: cs }))

    const calendarDays = [
        ...Array(getWeekday(date)).fill(null),
        ...Array.from({ length: getDaysInMonth(date) }, (_, i) => new Date(date.getFullYear(), date.getMonth(), i + 1)),
        ...Array((7 - ((getWeekday(date) + getDaysInMonth(date)) % 7)) % 7).fill(null)
    ]

    const renderFlight = flight => {
        const Icon = flight.icon
        return (
            <li
                key="flight"
                className="relative group">
                <div className="flex items-center space-x-1 text-white text-xs leading-tight">
                    <Icon className="w-4 h-4 mr-1 shrink-0" />
                    <span className="font-medium truncate hidden md:block">
                        {formatTimestamp(flight.time)}
                    </span>
                    <span className="whitespace-nowrap text-ellipsis truncate">
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

    const renderAbsence = (events, icon) => {
        const Icon = icon
        return events.map(event => (
            <li
                key={event.id}
                className="relative group">
                <div className="flex items-center space-x-1 text-white text-xs leading-tight">
                    <Icon className="w-4 h-4 mr-1 shrink-0" />
                    <span className="font-medium truncate">
                        {formatDuration((-1) * event.hours * 3600)}
                    </span>
                    {isAdmin && (
                        <button
                            className="p-0.5 btn-icon-hover"
                            onClick={() => handleRemoveEvent(event)}>
                            <Trash2 size={16} />
                        </button>
                    )}
                    <Tooltip>
                        <Icon size={16} />
                        {`Zbývá ${formatDuration(Math.min(...events.map(e => e.balance)) * 3600)}`}
                    </Tooltip>
                </div>
            </li>
        ))
    }

    const toNoonTimestamp = (date) => {
        const newDate = new Date(date)
        newDate.setHours(12, 0, 0, 0)
        return Math.floor(d.getTime() / 1000)
    }

    const handleCreatePositiveOvertimeEvent = (day, expectedWorkingHours) => {
        showFormToast(
            "Zadej údaje pro vytvoření přesčasu:",
            [
                { label: "Popis přesčasu", required: true },
                { label: "Počet hodin", value: Math.max(0, expectedWorkingHours - standardWorkingHoursPerWorkingDay).toFixed(1), required: true, type: "number", min: 0 }
            ],
            "Přesčas byl úspěšně vytvořen",
            "Nepodařilo se vytvořit přesčas",
            async (description, hours) => onEventCreated("overtime", description, hours, toNoonTimestamp(day))
        )
    }

    const handleBalanceUsageEvent = (day, type, title, success, error) => {
        showFormToast(
            title,
            [{ value: standardWorkingHoursPerWorkingDay.toFixed(1), required: true, type: "number", min: 0 }],
            success,
            error,
            async (hours) => onEventCreated(type, "Balance usage", (-1) * hours, toNoonTimestamp(day))
        )
    }

    const handleCreateNegativeOvertimeEvent = day => {
        handleBalanceUsageEvent(
            day,
            "overtime",
            "Zadej počet hodin k využití přesčasu:",
            "Přesčas byl úspěšně využit",
            "Nepodařilo se využít přesčas"
        )
    }

    const handleCreateVacationEvent = day => {
        handleBalanceUsageEvent(
            day,
            "vacation",
            "Zadej počet hodin k využití dovolené:",
            "Dovolená byl úspěšně využita",
            "Nepodařilo se využít dovolenou"
        )
    }

    const handleCreateSelfcareEvent = day => {
        handleBalanceUsageEvent(
            day,
            "selfcare",
            "Zadej počet hodin k využití sick daye:",
            "Sick day byl úspěšně využit",
            "Nepodařilo se využít sick day"
        )
    }

    const handleCreateTenureEvent = day => {
        handleBalanceUsageEvent(
            day,
            "tenure",
            "Zadej počet hodin k využití bonusového volna:",
            "Bonusové volno bylo úspěšně využito",
            "Nepodařilo se využít bonusové volno"
        )
    }

    const handleCreatePlannedWorkEvent = day => {
        showFormToast(
            "Zadej počet hodin k modifikaci plánované práce:",
            [{ value: standardWorkingHoursPerWorkingDay.toFixed(1), required: true, type: "number" }],
            "Plánovaná práce byla úspěšně modifikována",
            "Nepodařilo se modifikovat plánovanou práci",
            async (hours) => onEventCreated("plannedWork", "Planned work", hours, toNoonTimestamp(day))
        )
    }

    const handleRemoveEvent = event => {
        showConfirmToast(
            "Opravdu chceš odstranit tuto událost?",
            "Událost byla úspěšně odstraněna",
            "Nepodařilo se odstranit událost",
            async () => onEventRemoved(event.id)
        )
    }

    const handleCopyToClipboard = event => {
        showConfirmToast(
            "Text události bude vložen do schránky. Přeješ si pokračovat?",
            "Text události byl úspěšně vložen do schránky",
            "Nepodařilo se vložit text události do schránky",
            async () => navigator.clipboard.writeText(event.description)
        )
    }

    const renderButtons = (day, daySummary) => (
        <ul className="flex gap-1 list-none p-0 m-0">
            <li key="positiveOvertime">
                <button
                    className="flex items-center space-x-1 text-white text-xs leading-tight"
                    onClick={() => handleCreatePositiveOvertimeEvent(day, daySummary.expectedWorkingHours)}>
                    <Plus className="w-4 h-4 shrink-0 btn-icon-hover" />
                </button>
            </li>
            <li key="negativeOvertime">
                <button
                    className="flex items-center space-x-1 text-white text-xs leading-tight"
                    onClick={() => handleCreateNegativeOvertimeEvent(day)}>
                    <ClockPlus className="w-4 h-4 shrink-0 btn-icon-hover" />
                </button>
            </li>
            <li key="vacation">
                <button
                    className="flex items-center space-x-1 text-white text-xs leading-tight"
                    onClick={() => handleCreateVacationEvent(day)}>
                    <Palmtree className="w-4 h-4 shrink-0 btn-icon-hover" />
                </button>
            </li>
            <li key="selfcare">
                <button
                    className="flex items-center space-x-1 text-white text-xs leading-tight"
                    onClick={() => handleCreateSelfcareEvent(day)}>
                    <Pill className="w-4 h-4 shrink-0 btn-icon-hover" />
                </button>
            </li>
            <li key="tenure">
                <button
                    className="flex items-center space-x-1 text-white text-xs leading-tight"
                    onClick={() => handleCreateTenureEvent(day)}>
                    <Shield className="w-4 h-4 shrink-0 btn-icon-hover" />
                </button>
            </li>
            <li key="plannedWork">
                <button
                    className="flex items-center space-x-1 text-white text-xs leading-tight"
                    onClick={() => handleCreatePlannedWorkEvent(day)}>
                    <CalendarPlus className="w-4 h-4 shrink-0 btn-icon-hover" />
                </button>
            </li>
        </ul>
    )

    const getDaySummaryStyle = daySummary => {
        if (daySummary) {
            if (daySummary.isInTrip && (daySummary.actualWorkingHours > 0 || daySummary.expectedWorkingHours > 0)) {
                return {
                    bgColorClass: "bg-cyan-600 text-white",
                    hoverClass: "hover:bg-cyan-700",
                }
            }

            if (daySummary.standardWorkingHours === 0) {
                return {
                    bgColorClass: "bg-amber-600 text-white",
                    hoverClass: "hover:bg-amber-700",
                }
            }

            if (daySummary.isInTrip) {
                return {
                    bgColorClass: "bg-red-600 text-white",
                    hoverClass: "hover:bg-red-700",
                }
            }

            if (!daySummary.isInTrip && daySummary.standardWorkingHours > 0) {
                return {
                    bgColorClass: "bg-green-700 text-white",
                    hoverClass: "hover:bg-green-800",
                }
            }
        }

        return {
            bgColorClass: "bg-white",
            hoverClass: "hover:bg-white",
        }
    }

    return (
        <div className="w-full p-4 border rounded-md shadow-md bg-white text-black my-4 overflow-x-hidden">
            <div className="flex items-center justify-between mb-4">
                <button
                    onClick={() => !isPreviousMonthDisabled && changeMonth(-1)}
                    className={`p-2 rounded ${!isPreviousMonthDisabled ? "hover:bg-gray-200" : "opacity-50 cursor-not-allowed"}`}>
                    <ChevronLeft className="w-5 h-5" />
                </button>
                <h2 className="text-lg font-semibold">
                    {format(date, "LLLL yyyy", { locale: cs })}
                </h2>
                <div className="flex items-center space-x-2">
                    <button
                        onClick={goToToday}
                        className="p-2 rounded hover:bg-gray-200">
                        <Home className="w-5 h-5" />
                    </button>
                    <button
                        onClick={() => changeMonth(1)}
                        className="p-2 rounded hover:bg-gray-200">
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
                                className="border p-1 text-sm font-medium text-gray-600 select-none">
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
                                .map((dayDate, index) => {
                                    if (!dayDate) {
                                        return (
                                            <td
                                                key={index}
                                                className="border p-2 h-24" />
                                        )
                                    }

                                    const daySummary = getDaySummary(dayDate)
                                    const { bgColorClass, hoverClass } = getDaySummaryStyle(daySummary)

                                    return (
                                        <td
                                            key={index}
                                            className={`border py-1 px-2 align-top relative cursor-pointer select-none h-32 ${bgColorClass} ${hoverClass}`}>
                                            <div className="relative group w-full">
                                                <span className={`absolute top-0 right-0 text-xs font-semibold
                                                    ${startOfDay(dayDate).getTime() === startOfDay(now).getTime() ? "text-yellow-300 font-extrabold drop-shadow-[0_0_1px_yellow]" : ""}`}>
                                                    {dayDate.getDate()}
                                                </span>
                                                {isAdmin && (
                                                    <div className="absolute top-0 left-0 opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto w-full transition-opacity duration-200">
                                                        {renderButtons(dayDate, daySummary)}
                                                    </div>
                                                )}
                                            </div>
                                            <div className="flex flex-col h-full pt-6 text-white text-xs leading-tight">
                                                <div className="space-y-1 mb-2">
                                                    {daySummary && (
                                                        <>
                                                            <div className="flex items-center space-x-1 font-medium">
                                                                <Clock className="w-4 h-4 mr-1 shrink-0" />
                                                                <span className="truncate">
                                                                    {daySummary.day >= startOfDay(now)
                                                                        ? formatDuration(daySummary.expectedWorkingHours * 3600)
                                                                        : formatDuration(daySummary.actualWorkingHours * 3600)}
                                                                </span>
                                                            </div>
                                                            {daySummary.positiveOvertime.map(event => (
                                                                <div
                                                                    key={event.id}
                                                                    className="relative group">
                                                                    <div className="flex items-center space-x-1">
                                                                        <span
                                                                            className="text-2xs text-ellipsis truncate"
                                                                            onClick={isAdmin ? () => handleCopyToClipboard(event) : undefined}>
                                                                            {event.description}
                                                                        </span>
                                                                        {isAdmin && (
                                                                            <button
                                                                                className="p-0.5 btn-icon-hover"
                                                                                onClick={() => handleRemoveEvent(event)}>
                                                                                <Trash2 size={13} />
                                                                            </button>
                                                                        )}
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
                                                {daySummary && (
                                                    <ul className="space-y-1 mt-auto mb-1 m-0 p-0 list-none text-xs leading-tight">
                                                        {daySummary.flight && renderFlight(daySummary.flight)}
                                                        {daySummary.negativeOvertime?.length > 0 && renderAbsence(daySummary.negativeOvertime, ClockPlus)}
                                                        {daySummary.vacation?.length > 0 && renderAbsence(daySummary.vacation, Palmtree)}
                                                        {daySummary.selfcare?.length > 0 && renderAbsence(daySummary.selfcare, Pill)}
                                                        {daySummary.tenure?.length > 0 && renderAbsence(daySummary.tenure, Shield)}
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