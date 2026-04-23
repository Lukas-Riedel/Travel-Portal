import { useMemo } from "react"
import { Plane, Clock, MapPin, PlaneTakeoff, PlaneLanding, type LucideProps, type LucideIcon } from "lucide-react"
import { format, fromUnixTime } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import { Link } from "react-router-dom"
import { useTranslation } from "react-i18next"
import { useAirline } from "../hooks/useAirline.ts"
import LoadingCard from "./LoadingCard.tsx"
import { getSafeSvgString } from "../utils/imageUtils.ts"
import { useFormatters } from "../hooks/useFormatters.ts"
import { formatTimestamp, getCurrentTimestamp } from "../utils/timeUtils.ts"
import type { Airport, Flight } from "../types/CoreSwaggerTypes.ts"
import Card from "./Card.tsx"
import { getAircraftLink, getFlightLink, getFlightPriceLink } from "../utils/navigationUtils.ts"
import AppLink from "./AppLink.tsx"

interface FlightCardProps {
    flight: Flight | null
}

export default function FlightCard({ flight }: FlightCardProps) {
    const { t } = useTranslation()
    const { airline } = useAirline(flight?.airline?.id)
    const { formatDuration, formatKilometers } = useFormatters()

    if (!flight) {
        return (
            <LoadingCard />
        )
    }

    return (
        <Card>
            <div className="flex flex-col h-full">
                <div className="flex justify-between items-center mb-4">
                    <div className="flex flex-col space-y-1 text-gray-800 font-semibold">
                        <span className="text-sm text-gray-600">
                            {formatTimestamp(flight.start, t("general.format.date.year.included"), flight.from.timezone)}
                        </span>
                        <a
                            href={getFlightLink(flight.flight)}
                            className="text-blue-600 hover:underline text-lg"
                            target="_blank"
                            rel="noopener noreferrer">
                            {flight.flight}
                        </a>
                        {airline && (
                            <AppLink
                                to={airline}
                                className="text-base hover:underline">
                                {airline.name}
                            </AppLink>
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
                                {t("general.placeholder.logo")}
                            </div>
                        )}
                    </div>
                </div>
                <FlightAirport
                    airport={flight.from}
                    timestamp={flight.start}
                    Icon={PlaneTakeoff} />
                <FlightAirport
                    airport={flight.to}
                    timestamp={flight.end}
                    Icon={PlaneLanding} />
                <div className="mt-auto">
                    {flight.aircraft && flight.registration && (
                        <div className="text-gray-800 text-sm whitespace-nowrap my-1">
                            {flight.aircraft} (
                            <a
                                href={getAircraftLink(flight.registration)}
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
                        {flight.start > getCurrentTimestamp() && (
                            <a
                                href={getFlightPriceLink(flight)}
                                className="hover:underline"
                                target="_blank"
                                rel="noopener noreferrer">
                                {t("flight.label.watch")}
                            </a>
                        )}
                    </div>
                </div>
            </div>
        </Card>
    )
}

function FlightAirport({ airport, timestamp, Icon }: { airport: Airport, timestamp: number, Icon: LucideIcon }) {
    const { t } = useTranslation()

    return (
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
                    {formatTimestamp(timestamp, t("general.format.time"), airport.timezone)}
                </span>
            </div>
        </div>
    )
}