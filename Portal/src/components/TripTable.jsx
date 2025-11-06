import { fromUnixTime, eachDayOfInterval, startOfDay, addDays, differenceInCalendarDays, isSameDay } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import { getDateRangeString, getEvents, sumEventHours, isInTrip } from "../utils/helpers"
import { Link } from "react-router-dom"
import { TailSpin } from "react-loader-spinner"
import { useConfiguration } from "../contexts/ConfigContext"
import { useEffect, useMemo, useState } from "react"
import Tooltip from "./Tooltip"
import { ClockPlus } from "lucide-react"

const loadingRowsCount = 5

export default function TripTable({ trips, isFreeDay, overtimeEvents, plannedWorkEvents, vacationEvents, selfcareEvents, tenureEvents }) {
    const { configuration } = useConfiguration()

    const [isMobile, setIsMobile] = useState(() => window.innerWidth < 640)

    useEffect(() => {
        const onResize = () => setIsMobile(window.innerWidth < 640)
        onResize()
        window.addEventListener("resize", onResize)
        return () => window.removeEventListener("resize", onResize)
    }, [])

    const includePlannedTimeOff = isFreeDay && overtimeEvents && plannedWorkEvents && vacationEvents && selfcareEvents && tenureEvents

    const timezone = useMemo(() => configuration?.homeLocation?.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || "UTC", [configuration])
    const standardWorkingHoursPerWorkingDay = useMemo(() => 8 * configuration?.timeTracking?.currentFte || 8, [configuration])
    const expectedOvertimeHoursPerDay = useMemo(() => configuration?.timeTracking?.expectedOvertimeHoursPerDay || 0, [configuration])

    const latestAllowedDateStartOfDay = useMemo(() => startOfDay(fromUnixTime(trips?.at(-1)?.end)), [trips])

    const days = eachDayOfInterval({
        start: startOfDay(overtimeEvents?.some(event => isSameDay(fromUnixTime(event.timestamp), new Date())) ? addDays(new Date(), 1) : new Date()),
        end: latestAllowedDateStartOfDay
    })

    const tripBalances = useMemo(() => {
        const tripBalances = {}    

        if (includePlannedTimeOff) {
            let timeOffHoursNeededForCurrentTrip = 0
            let currentTimeOffHoursBalance = (vacationEvents?.[0]?.balance ?? 0) + (selfcareEvents?.[0]?.balance ?? 0) + (tenureEvents?.[0]?.balance ?? 0)
            let currentOvertimeHoursBalance = overtimeEvents?.[0]?.balance ?? 0

            for (let i = 0; i < days.length; ++i) {
                const startingTrip = trips?.find(trip => startOfDay(trip.start * 1000).getTime() === days[i].getTime())
                if (startingTrip) {
                    tripBalances[startingTrip.id] = {
                        availableOvertimeHours: currentOvertimeHoursBalance,
                        availableTimeOffHours: currentTimeOffHoursBalance
                    }
                }

                currentOvertimeHoursBalance += sumEventHours(getEvents(days[i], plannedWorkEvents, _ => true, timezone))
                const submittedTimeOffHours = (-1) * (sumEventHours(getEvents(days[i], vacationEvents, _ => true, timezone))
                    + sumEventHours(getEvents(days[i], selfcareEvents, _ => true, timezone))
                    + sumEventHours(getEvents(days[i], tenureEvents, _ => true, timezone))
                    + sumEventHours(getEvents(days[i], overtimeEvents, hours => hours < 0, timezone)))

                if (!isFreeDay(days[i])) {
                    if (isInTrip(trips, days[i])) {
                        currentOvertimeHoursBalance -= standardWorkingHoursPerWorkingDay - submittedTimeOffHours
                    }
                    else {
                        currentOvertimeHoursBalance += expectedOvertimeHoursPerDay
                    }
                }

                currentOvertimeHoursBalance = Math.round(currentOvertimeHoursBalance * 10) / 10

                if (currentOvertimeHoursBalance < 0) {
                    currentOvertimeHoursBalance += standardWorkingHoursPerWorkingDay - submittedTimeOffHours
                    timeOffHoursNeededForCurrentTrip += standardWorkingHoursPerWorkingDay - submittedTimeOffHours
                    currentTimeOffHoursBalance -= standardWorkingHoursPerWorkingDay - submittedTimeOffHours
                }

                const endingTrip = trips?.find(trip => startOfDay(trip.end * 1000).getTime() === days[i].getTime())
                if (endingTrip) {
                    if (endingTrip.id in tripBalances) {
                        tripBalances[endingTrip.id].timeOffHoursNeeded = timeOffHoursNeededForCurrentTrip
                    }
                    timeOffHoursNeededForCurrentTrip = 0
                }
            }
        }

        return tripBalances
    }, [overtimeEvents, plannedWorkEvents, vacationEvents, selfcareEvents, tenureEvents, days, standardWorkingHoursPerWorkingDay, trips, expectedOvertimeHoursPerDay])

    console.log(tripBalances)

    return (!trips || trips.length > 0) && (
        <div className="w-full rounded-xl my-4">
            <table className="w-full table-fixed divide-y divide-gray-200">
                <colgroup>
                    {includePlannedTimeOff && !isMobile ? (
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
                            Název
                        </th>
                        <th className="p-3 text-center">
                            Termín
                        </th>
                        <th className="p-3 text-center">
                            Rok
                        </th>
                        <th className="p-3 text-center">
                            Dnů
                        </th>
                        {includePlannedTimeOff && !isMobile && (
                            <>
                                <th className="p-3 text-center">
                                    Dostupných hodin přesčasů
                                </th>
                                <th className="p-3 text-center">
                                    Potřebných dnů volna
                                </th>
                                <th className="p-3 text-center">
                                    Dostupných dnů volna
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
                                <Link to={`/trip/${trip.id}`}>
                                    {trip.name}
                                </Link>
                            </td>
                            <td className="p-3 text-center">
                                {getDateRangeString(trip.start, trip.end, false)}
                            </td>
                            <td className="p-3 text-center">
                                <Link to={`/year/${trip.year}`}>
                                    {trip.year}
                                </Link>
                            </td>
                            <td className="p-3 text-center">
                                {differenceInCalendarDays(startOfDay(toZonedTime(fromUnixTime(trip.end - 1), timezone)), startOfDay(toZonedTime(fromUnixTime(trip.start), timezone))) + 1}
                            </td>
                            {includePlannedTimeOff && !isMobile && (
                                <>
                                    <td className="p-3 text-center relative group">
                                        {tripBalances[trip.id] ? (
                                            <>
                                                {tripBalances[trip.id].availableOvertimeHours.toFixed(1)}
                                                <Tooltip>
                                                    <ClockPlus size={16} />
                                                    Nejbližší další denní násobek je {(Math.ceil(tripBalances[trip.id].availableOvertimeHours / standardWorkingHoursPerWorkingDay) * standardWorkingHoursPerWorkingDay).toFixed(1)} hodin (zbývá {(Math.ceil(tripBalances[trip.id].availableOvertimeHours / standardWorkingHoursPerWorkingDay) * standardWorkingHoursPerWorkingDay - tripBalances[trip.id].availableOvertimeHours).toFixed(1)} hodin)
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
                    )) : Array.from({ length: loadingRowsCount })
                        .map((_, index) => (
                            <tr key={index}>
                                <td
                                    className="p-3"
                                    colSpan={includePlannedTimeOff && !isMobile ? 7 : 4}>
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
