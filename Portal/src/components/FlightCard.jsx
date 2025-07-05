import { Plane, Clock, MapPin, PlaneTakeoff, PlaneLanding } from "lucide-react"
import { format, fromUnixTime } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import { useConfiguration } from "../contexts/ConfigContext"
import { formatKilometers, formatDuration } from "../utils/formatters.js"
import { useMemo } from "react"
import { TailSpin } from "react-loader-spinner"
import { prefixSvgIds } from "../utils/helpers.js"

export default function FlightCard({ flight }) {
    const configuration = useConfiguration()

    const formatTime = (timestamp, timezone) => format(toZonedTime(fromUnixTime(timestamp), timezone), "HH:mm")

    const airlineName = useMemo(() => configuration?.airlines?.[flight?.flight?.substring(0, 2)], [configuration, flight])
    const airlineLogoSvg = useMemo(() => prefixSvgIds(configuration?.airlineLogos?.[flight?.flight?.substring(0, 2)], flight?.flight?.substring(0, 2)) || null, [configuration, flight])

    const renderFlightEndpoint = (airport, timestamp, Icon) => (
        <div className="my-2">
            <div className="flex items-center space-x-2">
                <Icon
                    size={16}
                    className="text-sky-600" />
                <a
                    href={`https://www.google.com/maps/search/Letiště ${airport.name} (${airport.code})`}
                    className="hover:underline text-sky-600 font-medium">
                    {airport.name} ({airport.code})
                </a>
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
                    <span className="text-base">
                        {airlineName}
                    </span>
                </div>
                <div className="flex-shrink-0 w-16 h-16 flex items-center justify-center">
                    {airlineLogoSvg ? (
                        <div
                            className="max-w-full max-h-full"
                            style={{
                                width: "100%",
                                height: "100%",
                                display: "flex",
                                alignItems: "center",
                                justifyContent: "center",
                            }}
                            dangerouslySetInnerHTML={{ __html: airlineLogoSvg }} />
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
