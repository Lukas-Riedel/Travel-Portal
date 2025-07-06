import { Plane, Clock, MapPin, PlaneTakeoff, PlaneLanding } from "lucide-react"
import { format, fromUnixTime } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import { formatKilometers, formatDuration } from "../utils/formatters.js"
import { useMemo } from "react"
import { TailSpin } from "react-loader-spinner"
import { getAirlineCodeForFlight, prefixSvgIds } from "../utils/helpers.js"
import { useAirlines } from "../hooks/useAirlines.js"
import { Link } from "react-router-dom"

export default function FlightCard({ flight }) {
    const airlines = useAirlines()

    const formatTime = (timestamp, timezone) => format(toZonedTime(fromUnixTime(timestamp), timezone), "HH:mm")

    const airlineCode = useMemo(() => getAirlineCodeForFlight(flight?.flight), [flight])
    const airline = useMemo(() => airlines?.find(airline => airline.code === airlineCode), [airlines, airlineCode])

    const renderFlightEndpoint = (airport, timestamp, Icon) => (
        <div className="my-2">
            <div className="flex items-center space-x-2">
                <Icon
                    size={16}
                    className="text-sky-600" />
                <Link
                    to={`/airport/${airport.id}`}
                    className="hover:underline text-sky-600 font-medium">
                    {airport.name} ({airport.code})
                </Link>
            </div>
            <div className="flex items-center space-x-2 text-gray-700 font-mono">
                <Clock size={16} />
                <span>
                    {formatTime(timestamp, airport.timezone)}
                </span>
            </div>
        </div>
    )

    return flight ? (
        <div className="bg-white rounded-xl shadow-md max-w-xl mx-auto p-3 w-full hover:shadow-lg transition-shadow duration-200">
            <div className="flex justify-between items-center mb-4">
                <div className="flex flex-col space-y-1 text-gray-800 font-semibold">
                    <span className="text-sm text-gray-600">
                        {format(toZonedTime(fromUnixTime(flight.start), flight.from.timezone), "d.M.yyyy")}
                    </span>
                    <a
                        href={`https://www.flightradar24.com/data/flights/${flight.flight}`}
                        className="text-blue-600 hover:underline text-lg">
                        {flight.flight}
                    </a>
                    {airline?.name && (
                        <Link
                            to={`/airline/${airline.name}`}
                            className="text-base hover:underline">
                            {airline.name}
                        </Link>
                    )}
                </div>
                <div className="flex-shrink-0 w-16 h-16 flex items-center justify-center">
                    {airline?.logo ? (
                        <div
                            className="max-w-full max-h-full"
                            style={{
                                width: "100%",
                                height: "100%",
                                display: "flex",
                                alignItems: "center",
                                justifyContent: "center",
                            }}
                            dangerouslySetInnerHTML={{ __html: prefixSvgIds(airline.logo, airlineCode) }} />
                    ) : (
                        <div className="text-gray-400 text-sm text-center">
                            Logo není k dispozici
                        </div>
                    )}
                </div>
            </div>
            {renderFlightEndpoint(flight.from, flight.start, PlaneTakeoff)}
            {renderFlightEndpoint(flight.to, flight.end, PlaneLanding)}
            <div className="text-gray-800 text-sm whitespace-nowrap my-1">
                {flight.aircraft} (
                <a
                    href={`https://www.flightradar24.com/data/aircraft/${flight.registration}`}
                    className="hover:underline">
                    {flight.registration}
                </a>
                )
            </div>
            <div className="flex justify-between text-[12px] text-gray-400 whitespace-nowrap my-0.5">
                <span>
                    {formatDuration(flight.end - flight.start)}
                </span>
                <span>
                    {formatKilometers(Math.round(flight.distance))}
                </span>
            </div>
        </div>
    ) : (
        <div className="fbg-white rounded-xl shadow p-4 flex flex-col items-center justify-center h-[150px]">
            <TailSpin
                color="black"
                height={30}
                width={30} />
        </div>
    )
}
