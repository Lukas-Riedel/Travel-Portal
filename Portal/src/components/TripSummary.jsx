import { useEffect, useMemo, useState } from "react"
import { eachDayOfInterval, fromUnixTime, startOfDay } from "date-fns"
import { getDateRangeString, getTimeString } from "../utils/helpers"
import DayCard from "./DayCard"
import { Link } from "react-router-dom"
import { useTrip } from "../hooks/useTrip"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { TailSpin } from "react-loader-spinner"
import { useConfiguration } from "../contexts/ConfigContext"
import { Battery, Clock, Earth, House, LocateFixedIcon, LocateOffIcon } from "lucide-react"
import { useEvents } from "../hooks/useEvents"
import { toZonedTime } from "date-fns-tz"
import { useAuth } from "../contexts/AuthContext"
import { useLastSeenBridgeXDevice } from "../hooks/useLastSeenBridgeXDevice"
import { formatTimeAgo } from "../utils/formatters"

export default function TripSummary({ tripId }) {
    const { isAdmin } = useAuth()
    const { configuration } = useConfiguration()
    const { publishPhotosUploadingTriggeredEvent } = useEvents()

    const { trip } = useTrip(tripId)
    const tripPlaces = useRegularPlaces({ tripId, include: "categories,dates" })
    const lastSeenBridgeXDevice = useLastSeenBridgeXDevice([
        ...(trip?.stays.map(stay => ({ name: stay.name, address: stay.address, radius: 0.15 })) ?? []),
        ...(trip?.flights.map(flight => ({ name: "Letiště " + flight.from.shortName, address: "Letiště " + flight.from.shortName, radius: 3.0 })) ?? []),
        ...(trip?.flights.map(flight => ({ name: "Letiště " + flight.to.shortName, address: "Letiště " + flight.to.shortName, radius: 3.0 })) ?? [])
    ])

    const [timezone, setTimezone] = useState(undefined)
    useEffect(() => {
        setTimezone(configuration?.homeLocation?.timezone)
    }, [configuration])

    const [count, setCount] = useState(3)
    useEffect(() => {
        const onResize = () => {
            if (window.innerWidth < 696) {
                setCount(1)
            }
            else if (window.innerWidth >= 696 && window.innerWidth < 968) {
                setCount(2)
            }
            else {
                setCount(3)
            }
        }
        onResize()
        window.addEventListener("resize", onResize)
        return () => window.removeEventListener("resize", onResize)
    }, [])

    const startOfTripStartDay = useMemo(() => startOfDay(fromUnixTime(trip?.start)), [trip])
    const days = useMemo(() => trip && eachDayOfInterval({
        start: startOfDay(toZonedTime(fromUnixTime(Math.max(trip?.start, Date.now() / 1000)), timezone)),
        end: startOfDay(toZonedTime(fromUnixTime(trip?.end - 1), timezone))
    }), [timezone, trip])
    const tripPlacesWithoutLayover = useMemo(() => trip && tripPlaces?.filter(place => !place.dates?.some(date => date?.layover)), [tripPlaces])
    const countryCategories = useMemo(() => [...new Map(tripPlacesWithoutLayover?.map(place => place.getCategory("country"))
        ?.filter(Boolean)?.map(category => [category.name, category])).values()].sort((a, b) => a.name.localeCompare(b.name)), [tripPlacesWithoutLayover])

    return trip ? (
        <div className="relative w-full grid grid-cols-[repeat(auto-fill,minmax(13rem,1fr))] items-center gap-4 bg-white p-3 my-4 text-sm">
            <div className="flex flex-col items-center justify-center text-center">
                {countryCategories && (
                    <div className="flex">
                        {countryCategories.map(category => (
                            <img
                                key={category.id}
                                className="w-7 object-cover mx-1.5 flex-shrink-0"
                                src={`/img/flags/${category.metadata?.unicode}.svg`}
                                alt={category.name} />
                        ))}
                    </div>)}
                <Link
                    className="my-2 text-2xl font-semibold"
                    to={`/trip/${trip.id}`}>
                    {trip.name}
                </Link>
                <div className="text-xl text-gray-700">
                    {getDateRangeString(trip.start, trip.end)}
                </div>
                {lastSeenBridgeXDevice && (isAdmin || trip.isCurrent()) && (
                    <>
                        {lastSeenBridgeXDevice.lastSeen + 1800 > Date.now() / 1000 ? (
                            <div className="flex items-center justify-center w-full text-green-600 space-x-1 mt-6 hover:underline hover:text-green-400 transition-colors duration-200">
                                <LocateFixedIcon size={16} />
                                <a
                                    className="text-xs truncate"
                                    href={`https://www.google.com/maps/search/${encodeURIComponent(lastSeenBridgeXDevice.address)}`}>
                                    {lastSeenBridgeXDevice.name}
                                </a>
                            </div>
                        ) : (
                            <div className="flex items-center justify-center w-full text-red-600 space-x-1 mt-6 hover:underline hover:text-red-400 transition-colors duration-200">
                                <LocateOffIcon size={16} />
                                <a
                                    className="text-xs truncate"
                                    href={`https://www.google.com/maps/search/${encodeURIComponent(lastSeenBridgeXDevice.address)}`}>
                                    {lastSeenBridgeXDevice.name}
                                </a>
                            </div>
                        )}
                        <ul className="text-[10px] text-gray-500 mt-2 space-y-0.5">
                            <li className="flex justify-center items-center space-x-2">
                                <div className="flex items-center space-x-1">
                                    <Clock className="w-3 h-3" />
                                    <span>
                                        {getTimeString(toZonedTime(new Date(), lastSeenBridgeXDevice.timezone).getTime() / 1000)}
                                    </span>
                                </div>
                                <span>|</span>
                                <div className="flex items-center space-x-1">
                                    <Battery className="w-3 h-3" />
                                    <span>
                                        {Math.round(lastSeenBridgeXDevice.battery)}%
                                    </span>
                                </div>
                            </li>
                            <li className="text-center">
                                Aktualizováno před {formatTimeAgo(lastSeenBridgeXDevice.lastSeen)}
                            </li>
                        </ul>

                    </>
                )}
            </div>
            {days?.map((day, index) => (
                <DayCard
                    key={index}
                    day={day}
                    events={tripPlaces && trip?.getEvents(day, tripPlaces, timezone)}
                    stay={trip?.getStay(day)}
                    fitness={trip?.fitness[(day - startOfTripStartDay) / (86400 * 1000)]}
                    publicHoliday={trip?.getPublicHoliday(day)}
                    timezone={timezone}
                    onPhotosAdded={publishPhotosUploadingTriggeredEvent} />
            ))?.filter(Boolean)?.slice(0, count)}
            <button
                onClick={() => setTimezone(prev => prev ? undefined : configuration?.homeLocation?.timezone)}
                className="absolute bottom-5 right-5 btn-chip-gray">
                {timezone ? <Earth size={16} /> : <House size={16} />}
            </button>
        </div>
    ) : (
        <div className="flex justify-center items-center min-h-[200px]">
            <TailSpin
                color="black"
                height={64}
                width={64} />
        </div>
    )
}