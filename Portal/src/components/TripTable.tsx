import { ClockPlus } from "lucide-react"
import {useEffect, useMemo, useState } from "react"
import { useTranslation } from "react-i18next"
import { TailSpin } from "react-loader-spinner"

import type { Trip } from "../types/CoreSwaggerTypes"
import { useConfiguration } from "../contexts/ConfigContext"
import { usePublicHolidays } from "../hooks/usePublicHolidays"
import type { TimeTrackingEvent } from "../types/CoreSwaggerTypes"
import { TimeTrackingEventType } from "../types/CoreSwaggerTypes"
import { getEventHoursSum, getEvents, HOURS_PER_MAN_DAY } from "../utils/eventUtils"
import { formatDateRange, getDaysFromTodayThrough, getTimezoneOrDefault, isBeginningOfCurrentYear, isToday } from "../utils/timeUtils"
import { getTripDaysCount, isDayInTrip, isEndDayOfTrip, isStartDayOfTrip } from "../utils/tripUtils"
import AppLink from "./AppLink"
import Tooltip from "./Tooltip"

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

    const timezone = getTimezoneOrDefault(configuration?.homeLocation?.timezone)
    const standardWorkingHoursPerWorkingDay = HOURS_PER_MAN_DAY * (configuration?.timeTracking?.currentFte ?? 1) || HOURS_PER_MAN_DAY
    const expectedOvertimeHoursPerDay = configuration?.timeTracking?.expectedOvertimePerDay as number || 0
    const openingTimeOffHours = (Object.values(configuration?.timeTracking?.openingBalance ?? {}) as number[]).reduce((sum, value) => sum + (value ?? 0), 0)

    const daysOffset = timeTrackingEvents?.[TimeTrackingEventType.Overtime]?.some(event => isToday(event.timestamp)) ? 1 : 0
    const days = getDaysFromTodayThrough(trips?.at(-1)?.end ?? 0, daysOffset)

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
                const day = days[i]
                if (day == null) {
                    continue
                }

                const startingTrip = trips?.find(trip => isStartDayOfTrip(trip, day))
                if (startingTrip) {
                    tripBalances[startingTrip.id] = {
                        availableOvertimeHours: currentExpectedOvertimeHoursBalance,
                        availableTimeOffHours: currentExpectedTimeOffHoursBalance
                    }
                }

                const doGetEventHoursSum = (eventType: TimeTrackingEventType, filterHours: (hours: number) => boolean = _ => true) =>
                    getEventHoursSum(getEvents(day, timeTrackingEvents?.[eventType] ?? null, filterHours, timezone))

                const submittedTimeOffHours = (-1) * (doGetEventHoursSum(TimeTrackingEventType.Vacation)
                    + doGetEventHoursSum(TimeTrackingEventType.Selfcare)
                    + doGetEventHoursSum(TimeTrackingEventType.Tenure)
                    + doGetEventHoursSum(TimeTrackingEventType.Overtime, hours => hours < 0))

                currentExpectedOvertimeHoursBalance += doGetEventHoursSum(TimeTrackingEventType.PlannedWork)

                if (!isFreeDay(day)) {
                    if ((trips ?? []).some(trip => isDayInTrip(trip, day))) {
                        currentExpectedOvertimeHoursBalance -= standardWorkingHoursPerWorkingDay - submittedTimeOffHours
                    }
                    else {
                        currentExpectedOvertimeHoursBalance += expectedOvertimeHoursPerDay
                    }
                }

                if (openingTimeOffHours && isBeginningOfCurrentYear(day)) {
                    currentExpectedTimeOffHoursBalance = Math.max(0, currentExpectedTimeOffHoursBalance) + openingTimeOffHours
                }

                currentExpectedOvertimeHoursBalance = Math.round(currentExpectedOvertimeHoursBalance * 10) / 10

                if (currentExpectedOvertimeHoursBalance < 0) {
                    currentExpectedOvertimeHoursBalance += standardWorkingHoursPerWorkingDay - submittedTimeOffHours
                    timeOffHoursNeededForCurrentTrip += standardWorkingHoursPerWorkingDay - submittedTimeOffHours
                    currentExpectedTimeOffHoursBalance -= standardWorkingHoursPerWorkingDay - submittedTimeOffHours
                }

                const endingTrip = trips?.find(trip => isEndDayOfTrip(trip, day))
                if (endingTrip) {
                    const endingBalance = tripBalances[endingTrip.id]
                    if (endingBalance) {
                        endingBalance.timeOffHoursNeeded = timeOffHoursNeededForCurrentTrip
                    }

                    timeOffHoursNeededForCurrentTrip = 0
                }
            }
        }

        return tripBalances
    }, [timeTrackingEvents, trips, days, standardWorkingHoursPerWorkingDay, expectedOvertimeHoursPerDay, isFreeDay])

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
                    {trips ? trips.map(trip => {
                        const balance = tripBalances[trip.id]
                        return (
                            <tr
                                key={trip.id}
                                className="hover:bg-gray-100">
                                <td className="p-3 text-center">
                                    <AppLink to={trip}>
                                        {trip.name}
                                    </AppLink>
                                </td>
                                <td className="p-3 text-center">
                                    {trip.start && trip.end && (
                                        formatDateRange(trip.start, trip.end, t("general.format.date.year.excluded"))
                                    )}
                                </td>
                                <td className="p-3 text-center">
                                    {trip.year && (
                                        <AppLink to={trip.year}>
                                            {trip.year}
                                        </AppLink>
                                    )}
                                </td>
                                <td className="p-3 text-center">
                                    {getTripDaysCount(trip, timezone)}
                                </td>
                                {timeTrackingEvents && !isMobile && (
                                    <>
                                        <td className="p-3 text-center relative group hover:cursor-help">
                                            {balance ? (
                                                <>
                                                    {balance.availableOvertimeHours.toFixed(1)}
                                                    <Tooltip>
                                                        <ClockPlus size={16} />
                                                        {t("tracker.label.hours.overtime.missing", {
                                                            nextFullDayHours: (Math.ceil(balance.availableOvertimeHours / standardWorkingHoursPerWorkingDay) * standardWorkingHoursPerWorkingDay).toFixed(1),
                                                            missingHours: (Math.ceil(balance.availableOvertimeHours / standardWorkingHoursPerWorkingDay) * standardWorkingHoursPerWorkingDay - balance.availableOvertimeHours).toFixed(1)
                                                        })}
                                                    </Tooltip>
                                                </>
                                            ) : "---"}
                                        </td>
                                        <td className="p-3 text-center">
                                            {balance ? ((balance.timeOffHoursNeeded ?? 0) / standardWorkingHoursPerWorkingDay).toFixed(0) : "---"}
                                        </td>
                                        <td className={`p-3 text-center ${balance && Math.round(balance.availableTimeOffHours) > (balance.timeOffHoursNeeded ?? 0) ? "text-green-600" : "text-red-600"}`}>
                                            {balance ? (+(balance.availableTimeOffHours / standardWorkingHoursPerWorkingDay).toFixed(0)) : "---"}
                                        </td>
                                    </>
                                )}
                            </tr>
                        )
                    }) : Array.from({ length: LOADING_ROWS_COUNT })
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
