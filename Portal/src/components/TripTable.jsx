import { format, fromUnixTime, eachDayOfInterval } from "date-fns"
import { getDateRangeString } from "../utils/helpers"
import { Link } from "react-router-dom"
import { TailSpin } from "react-loader-spinner"

const loadingRowsCount = 5

export default function TripTable({ trips }) {
    return (!trips || trips.length > 0) && (
        <div className="w-full rounded-xl my-4">
            <table className="w-full table-fixed divide-y divide-gray-200">
                <colgroup>
                    <col className="w-[30%]" />
                    <col className="w-[30%]" />
                    <col className="w-[20%]" />
                    <col className="w-[20%]" />
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
                                {eachDayOfInterval({ start: fromUnixTime(trip.start), end: fromUnixTime(trip.end) }).length}
                            </td>
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
