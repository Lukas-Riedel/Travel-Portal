import { fromUnixTime, eachDayOfInterval, startOfDay, addDays, differenceInCalendarDays } from "date-fns"
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
    const configuration = useConfiguration()

    const [isMobile, setIsMobile] = useState(() => window.innerWidth < 640)

    useEffect(() => {
        const onResize = () => setIsMobile(window.innerWidth < 640)
        window.addEventListener("resize", onResize)
        return () => window.removeEventListener("resize", onResize)
    }, [])

    const includePlannedTimeOff = isFreeDay && overtimeEvents && plannedWorkEvents && vacationEvents && selfcareEvents && tenureEvents
    const columns = useMemo(() => {
        const firstColumnWidth = 25
        const variableColumnsCount = includePlannedTimeOff && !isMobile ? 6 : 3
        return [firstColumnWidth, ...Array(variableColumnsCount).fill((100 - firstColumnWidth) / variableColumnsCount)]
    }, [includePlannedTimeOff, isMobile])

    const timezone = useMemo(() => configuration?.homeLocation?.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || "UTC", [configuration])
    const standardWorkingHoursPerWorkingDay = useMemo(() => 8 * configuration?.currentFte || 8, [configuration])

    const latestAllowedDateStartOfDay = useMemo(() => startOfDay(new Date(trips?.at(-1)?.end * 1000)), [trips])

    const days = eachDayOfInterval({
        start: startOfDay(startOfDay(overtimeEvents?.[0]?.timestamp ? addDays(new Date(overtimeEvents[0].timestamp * 1000), 1) : new Date())),
        end: latestAllowedDateStartOfDay
    })

    const tripBalances = useMemo(() => {
        const tripBalances = {}

        const getPlannedWork = day => getEvents(day, plannedWorkEvents, _ => true, timezone)
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

                currentOvertimeHoursBalance += sumEventHours(getPlannedWork(days[i]))

                if (!isFreeDay(days[i])) {
                    if (isInTrip(trips, days[i])) {
                        currentOvertimeHoursBalance -= standardWorkingHoursPerWorkingDay
                    }
                    else {
                        currentOvertimeHoursBalance += 8 - standardWorkingHoursPerWorkingDay
                    }
                }

                currentOvertimeHoursBalance = Math.round(currentOvertimeHoursBalance * 10) / 10

                if (currentOvertimeHoursBalance < 0) {
                    currentOvertimeHoursBalance += standardWorkingHoursPerWorkingDay
                    timeOffHoursNeededForCurrentTrip += standardWorkingHoursPerWorkingDay
                    currentTimeOffHoursBalance -= standardWorkingHoursPerWorkingDay
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
    }, [overtimeEvents, plannedWorkEvents, days, standardWorkingHoursPerWorkingDay, trips])

    console.log(trips)

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
                                    <td className={`p-3 text-center ${tripBalances[trip.id] && tripBalances[trip.id].availableTimeOffHours > tripBalances[trip.id].timeOffHoursNeeded ? "text-green-600" : "text-red-600"}`}>
                                        {tripBalances[trip.id] ? (tripBalances[trip.id].availableTimeOffHours / standardWorkingHoursPerWorkingDay).toFixed(0) : "---"}
                                    </td>
                                </>
                            )}
                        </tr>
                    )) : Array.from({ length: loadingRowsCount })
                        .map((_, idx) => (
                            <tr key={idx}>
                                <td
                                    className="p-3"
                                    colSpan={4}>
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
