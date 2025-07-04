import { fromUnixTime, eachDayOfInterval, startOfDay, addDays } from "date-fns"
import { getDateRangeString, getEvents, sumEventHours, isInTrip } from "../utils/helpers"
import { Link } from "react-router-dom"
import { TailSpin } from "react-loader-spinner"
import { useConfiguration } from "../contexts/ConfigContext"
import { useMemo } from "react"

const loadingRowsCount = 5

export default function TripTable({ trips, isFreeDay, overtimeEvents, plannedWorkEvents }) {
    const configuration = useConfiguration()

    const includePlannedTimeOff = isFreeDay && overtimeEvents && plannedWorkEvents
    const variableColumnsCount = includePlannedTimeOff ? 5 : 3
    const variableColumnWidth = 70 / variableColumnsCount
    const columns = [30, ...Array(variableColumnsCount).fill(variableColumnWidth)]

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
            let currentHoursBalance = overtimeEvents?.[0]?.balance ?? 0
            
            for (let i = 0; i < days.length; ++i) {
                const startingTrip = trips?.find(trip => startOfDay(trip.start * 1000).getTime() === days[i].getTime())
                if (startingTrip) {
                    tripBalances[startingTrip.id] = { availableHours: currentHoursBalance }
                }

                currentHoursBalance += sumEventHours(getPlannedWork(days[i]))

                if (!isFreeDay(days[i])) {
                    if (isInTrip(trips, days[i])) {
                        currentHoursBalance -= standardWorkingHoursPerWorkingDay
                    }
                    else {
                        currentHoursBalance += 8 - standardWorkingHoursPerWorkingDay
                    }
                }

                currentHoursBalance = Math.round(currentHoursBalance * 10) / 10

                if (currentHoursBalance < 0) {
                    currentHoursBalance += standardWorkingHoursPerWorkingDay
                    timeOffHoursNeededForCurrentTrip += standardWorkingHoursPerWorkingDay
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

    return (!trips || trips.length > 0) && (
        <div className="w-full rounded-xl my-4">
            <table className="w-full table-fixed divide-y divide-gray-200">
                <colgroup>
                    {columns.map((width, i) => (
                        <col
                            key={i}
                            style={{ width: `${width}%` }} />
                    ))}
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
                        {includePlannedTimeOff && (
                            <>
                                <th className="p-3 text-center">
                                    Dostupných hodin přesčasů
                                </th>
                                <th className="p-3 text-center">
                                    Potřebných dnů volna
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
                                {Math.floor((trip.end - trip.start) / 86400) + 1}
                            </td>
                            {includePlannedTimeOff && (
                                <>
                                    <td className="p-3 text-center">
                                        {tripBalances[trip.id] ? tripBalances[trip.id].availableHours.toFixed(1) : "---"}
                                    </td>
                                    <td className="p-3 text-center">
                                        {tripBalances[trip.id] ? (tripBalances[trip.id].timeOffHoursNeeded / standardWorkingHoursPerWorkingDay).toFixed(0) : "---"}
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
