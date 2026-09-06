import { fromUnixTime, eachDayOfInterval, startOfDay, addDays, differenceInCalendarDays } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import { getDateRangeString } from "../utils/helpers"
import { Link } from "react-router-dom"
import { TailSpin } from "react-loader-spinner"
import { useConfiguration } from "../contexts/ConfigContext"
import { useEffect, useMemo, useState } from "react"
import Tooltip from "./Tooltip"
import { ClockPlus } from "lucide-react"
import type { Trip } from "../classes/Trip"
import { TimeTrackingEventType } from "../types/CoreSwaggerTypes"
import type { TimeTrackingEvent } from "../types/CoreSwaggerTypes"
import { formatDateRange, getDaysFromTodayThrough, getTimezoneOrDefault, isBeginningOfCurrentYear, isToday } from "../utils/timeUtils"
import { getEvents, getEventHoursSum, HOURS_PER_MAN_DAY } from "../utils/eventUtils"
import { usePublicHolidays } from "../hooks/usePublicHolidays"
import { useTranslation } from "react-i18next"
import AppLink from "./AppLink"

const LOADING_ROWS_COUNT = 5

interface TripTableProps {
    trips: Trip[] | null
    timeTrackingEvents?: Partial<Record<TimeTrackingEventType, TimeTrackingEvent[]>>
}

export default function TripTable({ trips, timeTrackingEvents }: TripTableProps) {
    const { t } = useTranslation()
    const { configuration } = useConfiguration()
    const { isFreeDay } = usePublicHolidays(trips?.at(-1)?.year)

    // TODO: Rewrite to CSS.
    const [isMobile, setIsMobile] = useState(() => window.innerWidth < 640)
    useEffect(() => {
        const onResize = () => setIsMobile(window.innerWidth < 640)
        onResize()
        window.addEventListener("resize", onResize)
        return () => window.removeEventListener("resize", onResize)
    }, [])

    const timezone = useMemo(() => getTimezoneOrDefault(configuration?.homeLocation?.timezone), [configuration])
    const standardWorkingHoursPerWorkingDay = useMemo(() => HOURS_PER_MAN_DAY * configuration?.timeTracking?.currentFte || HOURS_PER_MAN_DAY, [configuration])
    const expectedOvertimeHoursPerDay = useMemo(() => configuration?.timeTracking?.expectedOvertimePerDay as number || 0, [configuration])
    const openingTimeOffHours = useMemo(() => (Object.values(configuration?.timeTracking?.openingBalance ?? {}) as number[]).reduce((sum, value) => sum + (value ?? 0), 0), [configuration])

    const daysOffset = timeTrackingEvents?.[TimeTrackingEventType.Overtime]?.some(event => isToday(event.timestamp)) ? 1 : 0
    const days = getDaysFromTodayThrough(trips?.at(-1)?.end, daysOffset)

    const tripBalances = useMemo(() => {
        const tripBalances: Record<string, { availableOvertimeHours: number, availableTimeOffHours: number, timeOffHoursNeeded?: number }> = {}

        const getEventTypeBalance = (eventType: TimeTrackingEventType) => timeTrackingEvents?.[eventType]?.[0]?.balance ?? 0

        if (timeTrackingEvents) {
            let timeOffHoursNeededForCurrentTrip = 0
            let currentExpectedOvertimeHoursBalance = getEventTypeBalance(TimeTrackingEventType.Overtime)
            let currentExpectedTimeOffHoursBalance = getEventTypeBalance(TimeTrackingEventType.Vacation)
                + getEventTypeBalance(TimeTrackingEventType.Selfcare)
                + getEventTypeBalance(TimeTrackingEventType.Tenure)

            for (let i = 0; i < days.length; ++i) {
                const startingTrip = trips?.find(trip => trip.isStartDayOfTrip(days[i]))
                if (startingTrip) {
                    tripBalances[startingTrip.id] = {
                        availableOvertimeHours: currentExpectedOvertimeHoursBalance,
                        availableTimeOffHours: currentExpectedTimeOffHoursBalance
                    }
                }

                const doGetEventHoursSum = (eventType: TimeTrackingEventType, filterHours: (hours: number) => boolean = _ => true) =>
                    getEventHoursSum(getEvents(days[i], timeTrackingEvents?.[eventType], filterHours, timezone))

                const submittedTimeOffHours = (-1) * (doGetEventHoursSum(TimeTrackingEventType.Vacation)
                    + doGetEventHoursSum(TimeTrackingEventType.Selfcare)
                    + doGetEventHoursSum(TimeTrackingEventType.Tenure)
                    + doGetEventHoursSum(TimeTrackingEventType.Overtime, hours => hours < 0))

                currentExpectedOvertimeHoursBalance += doGetEventHoursSum(TimeTrackingEventType.PlannedWork)

                if (!isFreeDay(days[i])) {
                    if (trips.some(trip => trip.isDayInTrip(days[i]))) {
                        currentExpectedOvertimeHoursBalance -= standardWorkingHoursPerWorkingDay - submittedTimeOffHours
                    }
                    else {
                        currentExpectedOvertimeHoursBalance += expectedOvertimeHoursPerDay
                    }
                }

                if (openingTimeOffHours && isBeginningOfCurrentYear(days[i])) {
                    currentExpectedTimeOffHoursBalance = Math.max(0, currentExpectedTimeOffHoursBalance) + openingTimeOffHours
                }

                currentExpectedOvertimeHoursBalance = Math.round(currentExpectedOvertimeHoursBalance * 10) / 10

                if (currentExpectedOvertimeHoursBalance < 0) {
                    currentExpectedOvertimeHoursBalance += standardWorkingHoursPerWorkingDay - submittedTimeOffHours
                    timeOffHoursNeededForCurrentTrip += standardWorkingHoursPerWorkingDay - submittedTimeOffHours
                    currentExpectedTimeOffHoursBalance -= standardWorkingHoursPerWorkingDay - submittedTimeOffHours
                }

                const endingTrip = trips?.find(trip => trip.isEndDayOfTrip(days[i]))
                if (endingTrip) {
                    if (endingTrip.id in tripBalances) {
                        tripBalances[endingTrip.id].timeOffHoursNeeded = timeOffHoursNeededForCurrentTrip
                    }

                    timeOffHoursNeededForCurrentTrip = 0
                }
            }
        }

        return tripBalances
    }, [timeTrackingEvents, trips, days, standardWorkingHoursPerWorkingDay, expectedOvertimeHoursPerDay])

    return (!trips || trips.length > 0) && (
        <div className="w-full rounded-xl my-4">
            <table className="w-full table-fixed divide-y divide-gray-200">
                <colgroup>
                    {timeTrackingEvents && !isMobile ? (
                        <>
                            <col className="w-[28%]" />
                            <col className="w-[12%]" />
                            <col className="w-[12%]" />
                            <col className="w-[12%]" />
                            <col className="w-[12%]" />
                            <col className="w-[12%]" />
                            <col className="w-[12%]" />
                        </>
                    ) : (
                        <>
                            <col className="w-[34%]" />
                            <col className="w-[22%]" />
                            <col className="w-[22%]" />
                            <col className="w-[22%]" />
                        </>
                    )}
                </colgroup>
                <thead className="bg-gray-100">
                    <tr>
                        <th className="p-3 text-center">
                            {t("trip.label.name")}
                        </th>
                        <th className="p-3 text-center">
                            {t("trip.label.dates")}
                        </th>
                        <th className="p-3 text-center">
                            {t("trip.label.year")}
                        </th>
                        <th className="p-3 text-center">
                            {t("trip.label.days")}
                        </th>
                        {timeTrackingEvents && !isMobile && (
                            <>
                                <th className="p-3 text-center">
                                    {t("tracker.label.hours.overtime.available")}
                                </th>
                                <th className="p-3 text-center">
                                    {t("tracker.label.days.timeOff.required")}
                                </th>
                                <th className="p-3 text-center">
                                    {t("tracker.label.days.timeOff.available")}
                                </th>
                            </>
                        )}
                    </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                    {trips ? trips.map(trip => (
                        <tr
                            key={trip.id}
                            className="hover:bg-gray-100">
                            <td className="p-3 text-center">
                                <AppLink to={trip}>
                                    {trip.name}
                                </AppLink>
                            </td>
                            <td className="p-3 text-center">
                                {formatDateRange(trip.start, trip.end, t("general.format.date.year.excluded"))}
                            </td>
                            <td className="p-3 text-center">
                                <AppLink to={trip.year}>
                                    {trip.year}
                                </AppLink>
                            </td>
                            <td className="p-3 text-center">
                                {trip.getDaysCount(timezone)}
                            </td>
                            {timeTrackingEvents && !isMobile && (
                                <>
                                    <td className="p-3 text-center relative group hover:cursor-help">
                                        {tripBalances[trip.id] ? (
                                            <>
                                                {tripBalances[trip.id].availableOvertimeHours.toFixed(1)}
                                                <Tooltip>
                                                    <ClockPlus size={16} />
                                                    {t("tracker.label.hours.overtime.missing", {
                                                        nextFullDayHours: (Math.ceil(tripBalances[trip.id].availableOvertimeHours / standardWorkingHoursPerWorkingDay) * standardWorkingHoursPerWorkingDay).toFixed(1),
                                                        missingHours: (Math.ceil(tripBalances[trip.id].availableOvertimeHours / standardWorkingHoursPerWorkingDay) * standardWorkingHoursPerWorkingDay - tripBalances[trip.id].availableOvertimeHours).toFixed(1)
                                                    })}
                                                </Tooltip>
                                            </>
                                        ) : "---"}
                                    </td>
                                    <td className="p-3 text-center">
                                        {tripBalances[trip.id] ? (tripBalances[trip.id].timeOffHoursNeeded / standardWorkingHoursPerWorkingDay).toFixed(0) : "---"}
                                    </td>
                                    <td className={`p-3 text-center ${tripBalances[trip.id] && Math.round(tripBalances[trip.id].availableTimeOffHours) > tripBalances[trip.id].timeOffHoursNeeded ? "text-green-600" : "text-red-600"}`}>
                                        {tripBalances[trip.id] ? (+(tripBalances[trip.id].availableTimeOffHours / standardWorkingHoursPerWorkingDay).toFixed(0)) : "---"}
                                    </td>
                                </>
                            )}
                        </tr>
                    )) : Array.from({ length: LOADING_ROWS_COUNT })
                        .map((_, index) => (
                            <tr key={index}>
                                <td
                                    className="p-3"
                                    colSpan={timeTrackingEvents && !isMobile ? 7 : 4}>
                                    <div className="flex justify-center items-center h-full w-full">
                                        <TailSpin
                                            color="black"
                                            height={24}
                                            width={24} />
                                    </div>
                                </td>
                            </tr>
                        ))}
                </tbody>
            </table>
        </div>
    )
}
