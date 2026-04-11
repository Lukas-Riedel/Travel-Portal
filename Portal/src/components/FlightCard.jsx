import { Plane, Clock, MapPin, PlaneTakeoff, PlaneLanding } from "lucide-react"
import { format, fromUnixTime } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import { Link } from "react-router-dom"
import { useAirline } from "../hooks/useAirline"
import LoadingCard from "./LoadingCard.tsx"
import { getSafeSvgString } from "../utils/imageUtils.ts"
import { useFormatters } from "../hooks/useFormatters.ts"

export default function FlightCard({ flight }) {
    const { airline } = useAirline(flight?.airline?.id)
    const { formatDuration, formatKilometers } = useFormatters()

    const formatTime = (timestamp, timezone) => format(toZonedTime(fromUnixTime(timestamp), timezone), "HH:mm")

    const renderFlightEndpoint = (airport, timestamp, Icon) => (
        <div className="my-2">
            <div className="flex items-center space-x-2">
                <Icon
                    size={16}
                    className="text-sky-600 flex-shrink-0" />
                {airport.id ? (
                    <Link
                        to={`/airport/${airport.id}`}
                        className="hover:underline text-sky-600 font-medium">
                        {airport.longName ?? `${airport.shortName} (${airport.code})`}
                    </Link>
                ) : (
                    <span className="text-sky-600 font-medium">
                        {airport.shortName}
                    </span>
                )}
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
        <div className="bg-white rounded-xl shadow-md max-w-xl mx-auto p-3 w-full">
            <div className="flex justify-between items-center mb-4">
                <div className="flex flex-col space-y-1 text-gray-800 font-semibold">
                    <span className="text-sm text-gray-600">
                        {format(toZonedTime(fromUnixTime(flight.start), flight.from.timezone), "d.M.yyyy")}
                    </span>
                    <a
                        href={`https://www.flightradar24.com/data/flights/${flight.flight}`}
                        className="text-blue-600 hover:underline text-lg"
                        target="_blank"
                        rel="noopener noreferrer">
                        {flight.flight}
                    </a>
                    {airline && (
                        <Link
                            to={`/airline/${airline.id}`}
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
                            dangerouslySetInnerHTML={{ __html: getSafeSvgString(airline.logo, airline.codes.join()) }} />
                    ) : (
                        <div className="text-gray-400 text-sm text-center">
                            Logo není k dispozici
                        </div>
                    )}
                </div>
            </div>
            {renderFlightEndpoint(flight.from, flight.start, PlaneTakeoff)}
            {renderFlightEndpoint(flight.to, flight.end, PlaneLanding)}
            {flight.aircraft && flight.registration && (
                <div className="text-gray-800 text-sm whitespace-nowrap my-1">
                    {flight.aircraft} (
                    <a
                        href={`https://www.flightradar24.com/data/aircraft/${flight.registration}`}
                        className="hover:underline"
                        target="_blank"
                        rel="noopener noreferrer">
                        {flight.registration}
                    </a>
                    )
                </div>
            )}
            <div className="flex justify-between text-[12px] text-gray-400 whitespace-nowrap my-0.5">
                <span>
                    {formatDuration(flight.end - flight.start)}
                </span>
                {flight.distance && (
                    <span>
                        {formatKilometers(Math.round(flight.distance))}
                    </span>
                )}
                {flight.start > Date.now() / 1000 && (
                    <a
                        href={`https://www.google.com/travel/flights?q=One way flight from ${flight.from.shortName} to ${flight.to.shortName} on ${format(toZonedTime(fromUnixTime(flight.start), flight.from.timezone), "d.M.yyyy")}`}
                        className="hover:underline"
                        target="_blank"
                        rel="noopener noreferrer">
                        Zkontrolovat cenu
                    </a>
                )}
            </div>
        </div>
    ) : (
        <LoadingCard />
    )
}
