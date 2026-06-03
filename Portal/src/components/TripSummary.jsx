import { useCallback, useEffect, useMemo, useState } from "react"
import { eachDayOfInterval, format, fromUnixTime, startOfDay } from "date-fns"
import { getCachedCoordinates, getDateRangeString, getTimeString } from "../utils/helpers"
import DayCard from "./DayCard.tsx"
import { Link } from "react-router-dom"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { TailSpin } from "react-loader-spinner"
import { useConfiguration } from "../contexts/ConfigContext"
import { Battery, Bed, Clock, Earth, House, LocateFixedIcon, LocateOffIcon, Sun, Moon, SunMoon } from "lucide-react"
import { useEvents } from "../hooks/useEvents"
import { toZonedTime } from "date-fns-tz"
import { useAuth } from "../contexts/AuthContext"
import { useLastSeenBridgeXDevice } from "../hooks/useLastSeenBridgeXDevice"
import { getCoordinates } from "../clients/coreClient"
import SunCalc from "suncalc"
import { getHaversineDistance } from "../utils/geocodingUtils.ts"
import { UserRole } from "../types/CoreSwaggerTypes.ts"
import { KnownAddressType } from "../types/KnownAddressType.ts"
import { useTranslation } from "react-i18next"
import { getCurrentTimestamp } from "../utils/timeUtils.ts"
import { getSunAltitude } from "../utils/sunUtils.ts"

export default function TripSummary({ trip, displayWarnings, onNoteAdded, onNoteRemoved }) {
    const { hasRole } = useAuth()
    const { configuration } = useConfiguration()
    const { publishPhotosUploadingTriggeredEvent, publishDayItinerarySharingRequestedEvent } = useEvents()
    const { t } = useTranslation()

    const { places } = useRegularPlaces({ tripId: trip?.id, include: ["categories", "dates"] })
    const lastSeenBridgeXDevice = useLastSeenBridgeXDevice([
        ...(trip?.stays?.map(stay => ({ name: stay.name, address: stay.address, type: KnownAddressType.Stay })) ?? []),
        ...(trip?.flights?.map(flight => ({ name: "Letiště " + flight.from.shortName, address: "Letiště " + flight.from.shortName, type: KnownAddressType.Airport })) ?? []),
        ...(trip?.flights?.map(flight => ({ name: "Letiště " + flight.to.shortName, address: "Letiště " + flight.to.shortName, type: KnownAddressType.Airport })) ?? [])
    ])

    const currentSunAltitude = useMemo(() => lastSeenBridgeXDevice?.data && Math.round(getSunAltitude(new Date(), lastSeenBridgeXDevice.data)), [lastSeenBridgeXDevice])
    const SunAltitudeIcon = useMemo(() => currentSunAltitude > 10 ? Sun : currentSunAltitude < -10 ? Moon : SunMoon, [currentSunAltitude])

    const [timezone, setTimezone] = useState(undefined)

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
    const tripPlacesWithoutLayover = useMemo(() => trip && places?.filter(place => !place.dates?.some(date => date?.layover)), [places])
    const countryCategories = useMemo(() => [...new Map(tripPlacesWithoutLayover?.map(place => place.getCategory("country"))
        ?.filter(Boolean)?.map(category => [category.name, category])).values()].sort((a, b) => a.name.localeCompare(b.name)), [tripPlacesWithoutLayover])

    const [targetLocation, setTargetLocation] = useState(null)

    const targetAddress = useMemo(() => trip?.getStay(startOfDay(new Date(Date.now() - 1000 * (toZonedTime(new Date(), lastSeenBridgeXDevice?.data?.timezone || "UTC").getHours() < 10 ? 86400 : 0))), configuration?.homeLocation?.timezone)?.address,
        [trip, lastSeenBridgeXDevice?.timezone])

    const formatRefreshedBefore = useCallback((timestamp) => {
        const seconds = Math.floor(Date.now() / 1000 - timestamp)
        if (seconds < 60) {
            return t("general.time.refreshed.ago.seconds")
        }

        const minutes = Math.floor(seconds / 60)
        if (minutes < 60) {
            return t("general.time.refreshed.ago.minute", { count: minutes })
        }

        const hours = Math.floor(minutes / 60)
        if (hours < 24) {
            return t("general.time.refreshed.ago.hour", { count: hours })
        }

        const days = Math.floor(hours / 24)
        return t("general.time.refreshed.ago.day", { count: days })
    }, [t])

    useEffect(() => {
        if (!targetAddress) {
            setTargetLocation(null)
            return
        }

        let isMounted = true
        getCachedCoordinates(targetAddress, getCoordinates).then(coordinates => {
            if (isMounted) {
                setTargetLocation(coordinates)
            }
        })

        return () => {
            isMounted = false
        }
    }, [targetAddress])

    const tripProgress = trip?.isCurrent() && (Math.min(Math.max(((getCurrentTimestamp() - trip.start) / (trip.end - trip.start)) * 100, 0), 100))

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
                {lastSeenBridgeXDevice && (hasRole(UserRole.PortalFutureRead) || trip.isCurrent()) && (
                    <>
                        {lastSeenBridgeXDevice.lastSeen + 1800 > Date.now() / 1000 ? (
                            <div className="flex items-center justify-center w-full text-green-600 space-x-1 mt-4 hover:underline hover:text-green-400 transition-colors duration-200">
                                <LocateFixedIcon size={16} />
                                <a
                                    className="text-xs truncate"
                                    href={`https://www.google.com/maps/search/${encodeURIComponent(lastSeenBridgeXDevice.data.address.address)}`}
                                    target="_blank"
                                    rel="noopener noreferrer">
                                    {lastSeenBridgeXDevice.data.address.name}
                                </a>
                            </div>
                        ) : (
                            <div className="flex items-center justify-center w-full text-red-600 space-x-1 mt-6 hover:underline hover:text-red-400 transition-colors duration-200">
                                <LocateOffIcon size={16} />
                                <a
                                    className="text-xs truncate"
                                    href={`https://www.google.com/maps/search/${encodeURIComponent(lastSeenBridgeXDevice.data.address.address)}`}
                                    target="_blank"
                                    rel="noopener noreferrer">
                                    {lastSeenBridgeXDevice.data.address.name}
                                </a>
                            </div>
                        )}
                        <ul className="text-[10px] text-gray-500 mt-2 space-y-0.5">
                            <li className="flex justify-center items-center space-x-1.5">
                                <div className="flex items-center space-x-1">
                                    <Clock className="w-3 h-3" />
                                    <span className="whitespace-nowrap">
                                        {getTimeString(toZonedTime(new Date(), lastSeenBridgeXDevice.data.timezone).getTime() / 1000)}
                                    </span>
                                </div>
                                {currentSunAltitude != null && (
                                    <>
                                        <span>|</span>
                                        <div className="flex items-center space-x-1">
                                            <SunAltitudeIcon className="w-3 h-3" />
                                            <span className="whitespace-nowrap">
                                                {currentSunAltitude} °
                                            </span>
                                        </div>
                                    </>
                                )}
                                <span>|</span>
                                <div className="flex items-center space-x-1">
                                    <Battery className="w-3 h-3" />
                                    <span className="whitespace-nowrap">
                                        {Math.round(lastSeenBridgeXDevice.data.battery)} %
                                    </span>
                                </div>
                                {targetLocation && (
                                    <>
                                        <span>|</span>
                                        <Bed className="w-3 h-3" />
                                        <span className="whitespace-nowrap">
                                            {Math.round(getHaversineDistance(targetLocation, lastSeenBridgeXDevice.data))} km
                                        </span>
                                    </>
                                )}
                                {configuration?.homeLocation && (
                                    <>
                                        <span>|</span>
                                        <House className="w-3 h-3" />
                                        <span className="whitespace-nowrap">
                                            {Math.round(getHaversineDistance(configuration.homeLocation, lastSeenBridgeXDevice.data))} km
                                        </span>
                                    </>
                                )}
                            </li>
                            <li className="text-center">
                                {formatRefreshedBefore(lastSeenBridgeXDevice.lastSeen)}
                            </li>
                        </ul>
                        {tripProgress > 0 && (
                            <div className="w-full mt-4">
                                <div className="w-full h-4 bg-gray-200 rounded-full dark:bg-gray-700 relative">
                                    <div
                                        className="h-4 bg-blue-500 rounded-full transition-all duration-500 ease-out flex items-center justify-center"
                                        style={{ width: `${tripProgress}%` }}>
                                        {tripProgress > 10 && (
                                            <span className="text-[10px] text-white leading-none">
                                                {Math.round(tripProgress)}%
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>
            {days?.map((day, index) => (
                <DayCard
                    key={index}
                    day={day}
                    events={places && trip?.getCalendarEvents(day, places, timezone)}
                    stay={trip?.getStay(day, configuration?.homeLocation?.timezone)}
                    fitness={trip?.fitness[(day - startOfTripStartDay) / (86400 * 1000)]}
                    noteSelector={prefix => trip?.notes?.filter(note => note.content.startsWith(prefix))?.map(note => ({ ...note, content: note.content.substring(prefix.length) }))}
                    publicHoliday={trip?.getPublicHoliday(day)}
                    timezone={timezone}
                    displayWarnings={displayWarnings}
                    onNoteAdded={onNoteAdded}
                    onNoteRemoved={onNoteRemoved}
                    // TODO: Propagate these two to the component arguments.
                    onPhotosAdded={hasRole(UserRole.PlaceAlbumEdit) && publishPhotosUploadingTriggeredEvent}
                    onItineraryShared={hasRole(UserRole.TripEdit) && (context => publishDayItinerarySharingRequestedEvent(trip.id, context, trip.getCalendarEvents(day, places, timezone), trip.getStay(day, configuration?.homeLocation?.timezone), trip.getPublicHoliday(day)))} />
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