import { useMemo, useState } from "react"
import { ChevronLeft, ChevronRight, Home, PlaneTakeoff, PlaneLanding, Clock, Shield, ClockPlus, Plane, Palmtree, Pill, Plus, Trash2, CalendarPlus, type LucideIcon } from "lucide-react"
import { format, getDaysInMonth, startOfMonth, getDay, startOfDay, endOfDay, addDays, startOfWeek, addMonths, endOfYear, isSameDay } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import { useConfiguration } from "../contexts/ConfigContext.tsx"
import { fromUnixTime } from "date-fns"
import Tooltip from "./Tooltip.tsx"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useUserInput } from "../hooks/useUserInput.tsx"
import { isInTrip } from "../utils/helpers.js"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { useFormatters } from "../hooks/useFormatters.ts"
import { getEvents, getEventHoursSum, HOURS_PER_MAN_DAY, TIME_TRACKING_EVENT_TYPE_ICONS } from "../utils/eventUtils.ts"
import type { Trip } from "../classes/Trip.ts"
import { TimeTrackingEventType, type TimeTrackingEvent } from "../types/CoreSwaggerTypes.ts"
import { usePublicHolidays } from "../hooks/usePublicHolidays.ts"
import { formatTimestamp, getNoonTimestamp, getTimezoneOrDefault, getWeekday, ONE_HOUR_SECONDS } from "../utils/timeUtils.ts"
import { useTranslation } from "react-i18next"
import { useLocale } from "../hooks/useLocale.ts"
import { getFlightLink } from "../utils/navigationUtils.ts"

const DAY_OF_WEEK_FORMAT = "EE"
const MONTH_YEAR_FORMAT = "LLLL yyyy"

const BALANCE_USAGE_EVENT_DESCRIPTION = "Balance usage"
const PLANNED_WORK_EVENT_DESCRIPTION = "Planned work"

interface TrackerCalendarProps {
    trips: Trip[] | null
    timeTrackingEvents?: Partial<Record<TimeTrackingEventType, TimeTrackingEvent[]>>
    onEventCreated?: (type: TimeTrackingEventType, description: string, hours: number, timestamp: number) => Promise<TimeTrackingEvent>
    onEventRemoved?: (eventId: string) => Promise<void>
}

export default function TrackerCalendar({ trips, timeTrackingEvents, onEventCreated, onEventRemoved }: TrackerCalendarProps) {
    const { configuration } = useConfiguration()
    const locale = useLocale()
    const { isFreeDay } = usePublicHolidays(trips?.at(-1)?.year)
    const { formatDuration } = useFormatters()
    const { showCreateNegativeTimeTrackingEventToast, showRemoveTimeTrackingEventToast, showCopyTimeTrackingEventDescriptionToast, showCreateOvertimeToast, showCreatePlannedWorkToast } = usePredefinedUserInput()

    const now = new Date()
    const timezone = useMemo(() => getTimezoneOrDefault(configuration?.homeLocation?.timezone), [configuration])
    const standardWorkingHoursPerWorkingDay = useMemo(() => HOURS_PER_MAN_DAY * configuration?.timeTracking?.currentFte || HOURS_PER_MAN_DAY, [configuration])
    const expectedOvertimeHoursPerDay = useMemo(() => configuration?.timeTracking?.expectedOvertimePerDay as number || 0, [configuration])

    const [date, setDate] = useState(() => startOfMonth(now))

    const setMonth = (offset: number) => setDate(previous => addMonths(previous, offset))
    const setToday = () => setDate(startOfMonth(now))

    const earliestAllowedDate = startOfMonth(addMonths(now, -1))
    const latestAllowedDate = useMemo(() => {
        const lastTripEnd = trips?.at(-1)?.end
        return endOfYear(lastTripEnd ? fromUnixTime(lastTripEnd) : now)
    }, [trips, now])

    const filteredTrips = useMemo(() => trips?.filter(trip => trip.isBetweenDates(earliestAllowedDate, latestAllowedDate, timezone)) ?? [],
        [trips, earliestAllowedDate, latestAllowedDate, timezone])

    const isPreviousMonthDisabled = useMemo(() => addMonths(date, -1) < earliestAllowedDate, [date, earliestAllowedDate])

    const daysOfWeek = Array.from({ length: 7 }, (_, i) => format(addDays(startOfWeek(new Date(), { weekStartsOn: 1 }), i), DAY_OF_WEEK_FORMAT, { locale }))

    const calendarDays: (Date | null)[] = [
        ...Array(getWeekday(date)).fill(null),
        ...Array.from({ length: getDaysInMonth(date) }, (_, i) => new Date(date.getFullYear(), date.getMonth(), i + 1)),
        ...Array((7 - ((getWeekday(date) + getDaysInMonth(date)) % 7)) % 7).fill(null)
    ]

    const getFlight = (day: Date) => {
        for (const { flights } of trips || []) {
            if (!flights || flights.length === 0) {
                continue
            }

            const firstFlight = flights[0]
            if (firstFlight && isSameDay(toZonedTime(fromUnixTime(firstFlight.start), timezone), day)) {
                return {
                    flight: firstFlight.flight,
                    from: firstFlight.from.shortName,
                    to: firstFlight.to.shortName,
                    time: firstFlight.start,
                    outbound: true
                }
            }

            const lastFlight = flights.at(-1)
            if (lastFlight && isSameDay(toZonedTime(fromUnixTime(lastFlight.end), timezone), day)) {
                return {
                    flight: lastFlight.flight,
                    from: lastFlight.from.shortName,
                    to: lastFlight.to.shortName,
                    time: lastFlight.end,
                    outbound: false
                }
            }
        }

        return null
    }

    const getDaySummary = (day: Date) => {
        if (day < earliestAllowedDate || day > latestAllowedDate) {
            return null
        }

        const standardWorkingHours = isFreeDay(day) ? 0 : standardWorkingHoursPerWorkingDay
        const isInTrip = filteredTrips.some(trip => trip.isDayInTrip(day))

        const positiveOvertime = getEvents(day, timeTrackingEvents?.[TimeTrackingEventType.Overtime], hours => hours > 0, timezone)
        const negativeOvertime = getEvents(day, timeTrackingEvents?.[TimeTrackingEventType.Overtime], hours => hours < 0, timezone)
        const vacation = getEvents(day, timeTrackingEvents?.[TimeTrackingEventType.Vacation], hours => hours < 0, timezone)
        const selfcare = getEvents(day, timeTrackingEvents?.[TimeTrackingEventType.Selfcare], hours => hours < 0, timezone)
        const tenure = getEvents(day, timeTrackingEvents?.[TimeTrackingEventType.Tenure], hours => hours < 0, timezone)
        const plannedWork = getEvents(day, timeTrackingEvents?.[TimeTrackingEventType.PlannedWork], _ => true, timezone)

        return {
            day,
            flight: getFlight(day),
            positiveOvertime,
            negativeOvertime,
            vacation,
            selfcare,
            tenure,
            plannedWork,
            isInTrip,
            standardWorkingHours,
            actualWorkingHours: (day > now ? 0 : standardWorkingHours)
                + getEventHoursSum(positiveOvertime)
                + getEventHoursSum(negativeOvertime)
                + getEventHoursSum(vacation)
                + getEventHoursSum(selfcare)
                + getEventHoursSum(tenure),
            expectedWorkingHours: (isFreeDay(day) || isInTrip ? 0 : (standardWorkingHours + expectedOvertimeHoursPerDay))
                + getEventHoursSum(plannedWork)
        }
    }

    const handleCreatePositiveOvertimeEvent = (day: Date, expectedWorkingHours: number) => {
        showCreateOvertimeToast(+Math.max(0, expectedWorkingHours - standardWorkingHoursPerWorkingDay).toFixed(1),
            async (description, hours) => onEventCreated(TimeTrackingEventType.Overtime, description, hours, getNoonTimestamp(day)))
    }

    const handleBalanceUsageEvent = (day: Date, type: TimeTrackingEventType) => {
        showCreateNegativeTimeTrackingEventToast(type, +standardWorkingHoursPerWorkingDay.toFixed(1),
            async (hours) => onEventCreated(type, BALANCE_USAGE_EVENT_DESCRIPTION, (-1) * hours, getNoonTimestamp(day)))
    }

    const handleCreateNegativeOvertimeEvent = (day: Date) => {
        handleBalanceUsageEvent(day, TimeTrackingEventType.Overtime)
    }

    const handleCreateVacationEvent = (day: Date) => {
        handleBalanceUsageEvent(day, TimeTrackingEventType.Vacation)
    }

    const handleCreateSelfcareEvent = (day: Date) => {
        handleBalanceUsageEvent(day, TimeTrackingEventType.Selfcare)
    }

    const handleCreateTenureEvent = (day: Date) => {
        handleBalanceUsageEvent(day, TimeTrackingEventType.Tenure)
    }

    const handleCreatePlannedWorkEvent = (day: Date) => {
        showCreatePlannedWorkToast(+standardWorkingHoursPerWorkingDay.toFixed(1),
            async (hours) => onEventCreated(TimeTrackingEventType.PlannedWork, PLANNED_WORK_EVENT_DESCRIPTION, hours, getNoonTimestamp(day)))
    }

    const handleRemoveEvent = (event: TimeTrackingEvent) => {
        showRemoveTimeTrackingEventToast(() => onEventRemoved(event.id))
    }

    const handleCopyToClipboard = (event: TimeTrackingEvent) => {
        showCopyTimeTrackingEventDescriptionToast(() => navigator.clipboard.writeText(event.description))
    }

    const getDaySummaryStyle = (daySummary: ReturnType<typeof getDaySummary>) => {
        if (daySummary) {
            if ((daySummary.isInTrip || daySummary.standardWorkingHours === 0) && (daySummary.actualWorkingHours > 0 || daySummary.expectedWorkingHours > 0)) {
                return {
                    bgColorClass: "bg-cyan-600 text-white",
                    hoverClass: "hover:bg-cyan-700"
                }
            }

            if (daySummary.standardWorkingHours === 0) {
                return {
                    bgColorClass: "bg-amber-600 text-white",
                    hoverClass: "hover:bg-amber-700"
                }
            }

            if (daySummary.isInTrip) {
                return {
                    bgColorClass: "bg-red-600 text-white",
                    hoverClass: "hover:bg-red-700"
                }
            }

            if (!daySummary.isInTrip && daySummary.standardWorkingHours > 0) {
                return {
                    bgColorClass: "bg-green-700 text-white",
                    hoverClass: "hover:bg-green-800"
                }
            }
        }

        return {
            bgColorClass: "bg-white",
            hoverClass: "hover:bg-white"
        }
    }

    return (
        <div className="w-full p-4 border rounded-md shadow-md bg-white text-black my-4 overflow-x-hidden">
            <div className="flex items-center justify-between mb-4">
                <button
                    onClick={() => !isPreviousMonthDisabled && setMonth(-1)}
                    className={`p-2 rounded ${!isPreviousMonthDisabled ? "hover:bg-gray-200" : "opacity-50 cursor-not-allowed"}`}>
                    <ChevronLeft className="w-5 h-5" />
                </button>
                <h2 className="text-lg font-semibold">
                    {format(date, MONTH_YEAR_FORMAT, { locale })}
                </h2>
                <div className="flex items-center space-x-2">
                    <button
                        onClick={setToday}
                        className="p-2 rounded hover:bg-gray-200">
                        <Home className="w-5 h-5" />
                    </button>
                    <button
                        onClick={() => setMonth(1)}
                        className="p-2 rounded hover:bg-gray-200">
                        <ChevronRight className="w-5 h-5" />
                    </button>
                </div>
            </div>
            <table className="w-full table-fixed border-collapse text-center">
                <thead>
                    <tr>
                        {daysOfWeek.map(day => (
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
                                                    ${isSameDay(dayDate, now) ? "text-yellow-300 font-extrabold drop-shadow-[0_0_1px_yellow]" : ""}`}>
                                                    {dayDate.getDate()}
                                                </span>
                                                {onEventCreated && (
                                                    <div className="absolute top-0 left-0 opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto w-full transition-opacity duration-200">
                                                        <ul className="flex gap-1 list-none p-0 m-0">
                                                            <li key="positiveOvertime">
                                                                <button
                                                                    className="flex items-center space-x-1 text-white text-xs leading-tight"
                                                                    onClick={() => handleCreatePositiveOvertimeEvent(dayDate, daySummary.expectedWorkingHours)}>
                                                                    <Plus className="w-4 h-4 shrink-0 btn-icon-hover" />
                                                                </button>
                                                            </li>
                                                            <li key="negativeOvertime">
                                                                <button
                                                                    className="flex items-center space-x-1 text-white text-xs leading-tight"
                                                                    onClick={() => handleCreateNegativeOvertimeEvent(dayDate)}>
                                                                    <ClockPlus className="w-4 h-4 shrink-0 btn-icon-hover" />
                                                                </button>
                                                            </li>
                                                            <li key="vacation">
                                                                <button
                                                                    className="flex items-center space-x-1 text-white text-xs leading-tight"
                                                                    onClick={() => handleCreateVacationEvent(dayDate)}>
                                                                    <Palmtree className="w-4 h-4 shrink-0 btn-icon-hover" />
                                                                </button>
                                                            </li>
                                                            <li key="selfcare">
                                                                <button
                                                                    className="flex items-center space-x-1 text-white text-xs leading-tight"
                                                                    onClick={() => handleCreateSelfcareEvent(dayDate)}>
                                                                    <Pill className="w-4 h-4 shrink-0 btn-icon-hover" />
                                                                </button>
                                                            </li>
                                                            <li key="tenure">
                                                                <button
                                                                    className="flex items-center space-x-1 text-white text-xs leading-tight"
                                                                    onClick={() => handleCreateTenureEvent(dayDate)}>
                                                                    <Shield className="w-4 h-4 shrink-0 btn-icon-hover" />
                                                                </button>
                                                            </li>
                                                            <li key="plannedWork">
                                                                <button
                                                                    className="flex items-center space-x-1 text-white text-xs leading-tight"
                                                                    onClick={() => handleCreatePlannedWorkEvent(dayDate)}>
                                                                    <CalendarPlus className="w-4 h-4 shrink-0 btn-icon-hover" />
                                                                </button>
                                                            </li>
                                                        </ul>
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
                                                                        ? formatDuration(daySummary.expectedWorkingHours * ONE_HOUR_SECONDS)
                                                                        : formatDuration(daySummary.actualWorkingHours * ONE_HOUR_SECONDS)}
                                                                </span>
                                                            </div>
                                                            {daySummary.positiveOvertime.map(event => (
                                                                <div
                                                                    key={event.id}
                                                                    className="relative group hover:cursor-help">
                                                                    <div className="flex items-center space-x-1">
                                                                        <span
                                                                            className="text-2xs text-ellipsis truncate"
                                                                            onClick={() => handleCopyToClipboard(event)}>
                                                                            {event.description}
                                                                        </span>
                                                                        {onEventRemoved && (
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
                                                        {daySummary.flight && (
                                                            <FlightInfo
                                                                flight={daySummary.flight.flight}
                                                                from={daySummary.flight.from}
                                                                to={daySummary.flight.to}
                                                                time={daySummary.flight.time}
                                                                timezone={timezone}
                                                                outbound={daySummary.flight.outbound} />
                                                        )}
                                                        {daySummary.negativeOvertime?.length > 0 && (
                                                            <AbsenceInfo
                                                                timeTrackingEventType={TimeTrackingEventType.Overtime}
                                                                timeTrackingEvents={daySummary.negativeOvertime}
                                                                onEventRemoved={onEventRemoved} />
                                                        )}
                                                        {daySummary.vacation?.length > 0 && (
                                                            <AbsenceInfo
                                                                timeTrackingEventType={TimeTrackingEventType.Vacation}
                                                                timeTrackingEvents={daySummary.vacation}
                                                                onEventRemoved={onEventRemoved} />
                                                        )}
                                                        {daySummary.selfcare?.length > 0 && (
                                                            <AbsenceInfo
                                                                timeTrackingEventType={TimeTrackingEventType.Selfcare}
                                                                timeTrackingEvents={daySummary.selfcare}
                                                                onEventRemoved={onEventRemoved} />
                                                        )}
                                                        {daySummary.tenure?.length > 0 && (
                                                            <AbsenceInfo
                                                                timeTrackingEventType={TimeTrackingEventType.Tenure}
                                                                timeTrackingEvents={daySummary.tenure}
                                                                onEventRemoved={onEventRemoved} />
                                                        )}
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

interface FlightInfoProps {
    flight: string
    from: string
    to: string
    time: number
    timezone: string
    outbound: boolean
}

function FlightInfo({ flight, from, to, time, timezone, outbound }: FlightInfoProps) {
    const { t } = useTranslation()

    const Icon = outbound ? PlaneTakeoff : PlaneLanding

    return (
        <li
            key="flight"
            className="relative group">
            <div className="flex items-center space-x-1 text-white text-xs leading-tight hover:cursor-help">
                <Icon className="w-4 h-4 mr-1 shrink-0" />
                <span className="font-medium truncate hidden md:block">
                    {formatTimestamp(time, t("general.format.time"), timezone)}
                </span>
                <span className="whitespace-nowrap text-ellipsis truncate">
                    <a
                        href={getFlightLink(flight)}
                        className="hover:underline hover:text-gray-300 transition-colors duration-200"
                        target="_blank"
                        rel="noopener noreferrer">
                        {flight}
                    </a>
                </span>
            </div>
            <Tooltip>
                <Plane size={16} />
                {`${from} → ${to}`}
            </Tooltip>
        </li>
    )
}

interface AbsenceInfoProps {
    timeTrackingEventType: TimeTrackingEventType
    timeTrackingEvents: TimeTrackingEvent[]
    onEventRemoved?: (eventId: string) => Promise<void>
}

function AbsenceInfo({ timeTrackingEventType, timeTrackingEvents, onEventRemoved }: AbsenceInfoProps) {
    const { t } = useTranslation()
    const { showRemoveTimeTrackingEventToast } = usePredefinedUserInput()
    const { formatDuration } = useFormatters()

    const handleRemoveEvent = (event: TimeTrackingEvent) => {
        if (onEventRemoved) {
            showRemoveTimeTrackingEventToast(() => onEventRemoved(event.id))
        }
    }

    const Icon = TIME_TRACKING_EVENT_TYPE_ICONS[timeTrackingEventType]

    return timeTrackingEvents.map(event => (
        <li
            key={event.id}
            className="relative group">
            <div className="flex items-center space-x-1 text-white text-xs leading-tight hover:cursor-help">
                <Icon className="w-4 h-4 mr-1 shrink-0" />
                <span className="font-medium truncate">
                    {formatDuration((-1) * event.hours * ONE_HOUR_SECONDS)}
                </span>
                {onEventRemoved && (
                    <button
                        className="p-0.5 btn-icon-hover"
                        onClick={() => handleRemoveEvent(event)}>
                        <Trash2 size={16} />
                    </button>
                )}
                <Tooltip>
                    <Icon size={16} />
                    {t("general.time.remaining", { duration: formatDuration(Math.min(...timeTrackingEvents.map(e => e.balance)) * ONE_HOUR_SECONDS) })}
                </Tooltip>
            </div>
        </li>
    ))
}