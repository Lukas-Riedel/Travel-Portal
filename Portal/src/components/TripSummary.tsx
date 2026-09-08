import { fromUnixTime, startOfDay } from "date-fns"
import { Battery, Bed, Clock, Earth, House, LocateFixedIcon, LocateOffIcon, type LucideIcon, Moon, Sun, SunMoon } from "lucide-react"
import { useEffect, useMemo, useState } from "react"
import { useTranslation } from "react-i18next"
import { TailSpin } from "react-loader-spinner"

import type { Trip } from "../classes/Trip.ts"
import { useConfiguration } from "../contexts/ConfigContext"
import { useCachedCoordinates } from "../hooks/useCachedCoordinates.ts"
import { useFormatters } from "../hooks/useFormatters.ts"
import { useLastSeenBridgeXDevice } from "../hooks/useLastSeenBridgeXDevice"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import type { Coordinates } from "../types/Coordinates.ts"
import type { Date as PlaceDate, Flight, Note, Place } from "../types/CoreSwaggerTypes.ts"
import { CategoryCategory, PlaceIncludedEntity } from "../types/CoreSwaggerTypes.ts"
import { KnownAddressType } from "../types/KnownAddressType.ts"
import { getHaversineDistance } from "../utils/geocodingUtils.ts"
import { getMapLink } from "../utils/navigationUtils.ts"
import { getSunAltitude } from "../utils/sunUtils.ts"
import { formatDateRange, formatTimestamp, getCurrentHour, getCurrentTimestamp, getTripDays, isTodayOrFutureDay, ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import AppLink from "./AppLink.tsx"
import CategoryFlag from "./CategoryFlag.tsx"
import DayCard from "./DayCard.tsx"

const SUNSET_OR_SUNRISE_SUN_ALTITUDE_THRESHOLD = 10
const HOTEL_STANDARD_CHECK_OUT_HOUR = 10
const TRIP_PROGRESS_THRESHOLD_PERCENT = 10
const LOCATION_UNAVAILABLE_THRESHOLD_SECONDS = 1800

interface TripSummaryProps {
    trip: Trip | null
    displayDeviceData?: boolean
    displayWarnings?: boolean
    onNoteAdded?: (content: string) => Promise<Note>
    onNoteRemoved?: (noteId: string) => Promise<void>
    onPhotosAdded?: (agentId: string, placeId: string, placeName: string, path: string, sendNotification: boolean, albumId?: string, timestamp?: number, mainPhotoPosition?: number) => Promise<void>
}

export default function TripSummary({ trip, displayDeviceData, displayWarnings, onNoteAdded, onNoteRemoved, onPhotosAdded }: TripSummaryProps) {
    const { configuration } = useConfiguration()
    const { t } = useTranslation()
    const { formatRefreshedBefore } = useFormatters()
    const getCachedCoordinates = useCachedCoordinates()

    const { places } = useRegularPlaces({ tripId: trip?.id, include: [PlaceIncludedEntity.Categories, PlaceIncludedEntity.Dates, PlaceIncludedEntity.Notes], enabled: !!trip?.id })
    const lastSeenBridgeXDevice = useLastSeenBridgeXDevice([
        ...(trip?.stays?.filter(stay => stay.address)?.map(stay => ({ name: stay.name, address: stay.address!, type: KnownAddressType.Stay })) ?? []),
        ...(trip?.flights?.map(flight => ({ name: t("airport.format", { name: flight.from.shortName }), address: t("airport.format", { name: flight.from.shortName }), type: KnownAddressType.Airport })) ?? []),
        ...(trip?.flights?.map(flight => ({ name: t("airport.format", { name: flight.to.shortName }), address: t("airport.format", { name: flight.to.shortName }), type: KnownAddressType.Airport })) ?? [])
    ])

    const currentSunAltitude = useMemo(() => lastSeenBridgeXDevice?.data?.latitude && lastSeenBridgeXDevice?.data?.longitude ? Math.round(getSunAltitude(getCurrentTimestamp(), lastSeenBridgeXDevice.data as Coordinates)) : undefined, [lastSeenBridgeXDevice?.data])
    const SunAltitudeIcon = useMemo<LucideIcon>(() => currentSunAltitude != null && currentSunAltitude > SUNSET_OR_SUNRISE_SUN_ALTITUDE_THRESHOLD ? Sun : currentSunAltitude != null && currentSunAltitude < (-1) * SUNSET_OR_SUNRISE_SUN_ALTITUDE_THRESHOLD ? Moon : SunMoon, [currentSunAltitude])

    const [timezone, setTimezone] = useState<string | undefined>(undefined)

    // TODO: Rewrite to CSS.
    const [dayCardsCount, setDayCardsCount] = useState(3)
    useEffect(() => {
        const onResize = () => {
            if (window.innerWidth < 696) {
                setDayCardsCount(1)
            }
            else if (window.innerWidth >= 696 && window.innerWidth < 968) {
                setDayCardsCount(2)
            }
            else {
                setDayCardsCount(3)
            }
        }
        onResize()
        window.addEventListener("resize", onResize)
        return () => window.removeEventListener("resize", onResize)
    }, [])

    const days = useMemo(() => trip && getTripDays(trip, places, timezone)?.filter(date => isTodayOrFutureDay(date, timezone)), [trip, places, timezone])

    const tripPlacesWithoutLayover = useMemo(() => trip && places?.filter(place => !place.dates?.some(date => date?.layover)), [trip, places])
    const countryCategories = useMemo(() => [...new Map(tripPlacesWithoutLayover?.map(place => place.getCategory(CategoryCategory.Country))
        ?.filter((c): c is NonNullable<typeof c> => c != null)?.map(category => [category.name, category])).values()].sort((a, b) => a.name.localeCompare(b.name)), [tripPlacesWithoutLayover])

    const [targetLocation, setTargetLocation] = useState<Coordinates | null>(null)

    // TODO: Make this expression more readable.
    const targetAddress = useMemo(() => trip?.getStay(startOfDay(new Date(Date.now() - 1000 * (getCurrentHour(lastSeenBridgeXDevice?.data?.timezone) < HOTEL_STANDARD_CHECK_OUT_HOUR ? ONE_DAY_SECONDS : 0))), configuration?.homeLocation?.timezone)?.address,
        [trip, lastSeenBridgeXDevice?.data?.timezone, configuration?.homeLocation?.timezone])

    useEffect(() => {
        if (!targetAddress) {
            setTargetLocation(null)
            return
        }

        let isMounted = true
        getCachedCoordinates(targetAddress).then(coordinates => {
            if (isMounted) {
                setTargetLocation(coordinates)
            }
        })

        return () => {
            isMounted = false
        }
    }, [targetAddress])

    const tripProgress = trip?.isCurrent() ? Math.min(Math.max(((getCurrentTimestamp() - (trip.start ?? 0)) / ((trip.end ?? 1) - (trip.start ?? 0))) * 100, 0), 100) : undefined

    if (!trip) {
        return (
            <div className="flex justify-center items-center min-h-[200px]">
                <TailSpin
                    color="black"
                    height={64}
                    width={64} />
            </div>
        )
    }

    return (
        <div className="relative w-full grid grid-cols-[repeat(auto-fill,minmax(13rem,1fr))] items-center gap-4 bg-white p-3 my-4 text-sm">
            <div className="flex flex-col items-center justify-center text-center">
                {countryCategories && (
                    <div className="flex">
                        {countryCategories.map(category => (
                            <CategoryFlag
                                key={category.id}
                                category={category}
                                className="w-7 object-cover mx-1.5 flex-shrink-0" />
                        ))}
                    </div>
                )}
                <AppLink
                    to={trip}
                    className="my-2 text-2xl font-semibold">
                    {trip.name}
                </AppLink>
                {trip.start && trip.end && (
                    <div className="text-xl text-gray-700">
                        {formatDateRange(trip.start, trip.end, t("general.format.date.year.included"))}
                    </div>
                )}
                {lastSeenBridgeXDevice && (displayDeviceData || trip.isCurrent()) && (
                    <>
                        {lastSeenBridgeXDevice.lastSeen + LOCATION_UNAVAILABLE_THRESHOLD_SECONDS > getCurrentTimestamp() ? (
                            <div className="flex items-center justify-center w-full text-green-600 space-x-1 mt-4 link-hover hover:text-green-400">
                                <LocateFixedIcon size={16} />
                                {lastSeenBridgeXDevice.data.address?.address ? (
                                    <a
                                        className="text-xs truncate"
                                        href={getMapLink(lastSeenBridgeXDevice.data.address?.address)}
                                        target="_blank"
                                        rel="noopener noreferrer">
                                        {lastSeenBridgeXDevice.data.address?.name}
                                    </a>
                                ) : (
                                    <span className="text-xs truncate">
                                        {lastSeenBridgeXDevice.data.address?.name}
                                    </span>
                                )}
                            </div>
                        ) : (
                            <div className="flex items-center justify-center w-full text-red-600 space-x-1 mt-6 link-hover hover:text-red-400">
                                <LocateOffIcon size={16} />
                                <a
                                    className="text-xs truncate"
                                    href={getMapLink(lastSeenBridgeXDevice.data.address?.address ?? "")}
                                    target="_blank"
                                    rel="noopener noreferrer">
                                    {lastSeenBridgeXDevice.data.address?.name}
                                </a>
                            </div>
                        )}
                        <ul className="text-[10px] text-gray-500 mt-2 space-y-0.5">
                            {/** TODO: Rewrite to a list of components being joined by a delimiter component. */}
                            <li className="flex justify-center items-center space-x-1.5">
                                {lastSeenBridgeXDevice.data.timezone && (
                                    <div className="flex items-center space-x-1">
                                        <Clock className="w-3 h-3" />
                                        <span className="whitespace-nowrap">
                                            {formatTimestamp(getCurrentTimestamp(), t("general.format.time"), lastSeenBridgeXDevice.data.timezone)}
                                        </span>
                                    </div>
                                )}
                                {currentSunAltitude != null && (
                                    <>
                                        <span>|</span>
                                        <div className="flex items-center space-x-1">
                                            <SunAltitudeIcon className="w-3 h-3" />
                                            <span className="whitespace-nowrap">
                                                {currentSunAltitude}°
                                            </span>
                                        </div>
                                    </>
                                )}
                                {lastSeenBridgeXDevice.data.battery && (
                                    <>
                                        <span>|</span>
                                        <div className="flex items-center space-x-1">
                                            <Battery className="w-3 h-3" />
                                            <span className="whitespace-nowrap">
                                                {Math.round(lastSeenBridgeXDevice.data.battery)}%
                                            </span>
                                        </div>
                                    </>
                                )}
                                {targetLocation && lastSeenBridgeXDevice.data.latitude && lastSeenBridgeXDevice.data.longitude && (
                                    <>
                                        <span>|</span>
                                        <Bed className="w-3 h-3" />
                                        <span className="whitespace-nowrap">
                                            {Math.round(getHaversineDistance(targetLocation, lastSeenBridgeXDevice.data as Coordinates))} km
                                        </span>
                                    </>
                                )}
                                {configuration?.homeLocation && lastSeenBridgeXDevice.data.latitude && lastSeenBridgeXDevice.data.longitude && (
                                    <>
                                        <span>|</span>
                                        <House className="w-3 h-3" />
                                        <span className="whitespace-nowrap">
                                            {Math.round(getHaversineDistance(configuration.homeLocation, lastSeenBridgeXDevice.data as Coordinates))} km
                                        </span>
                                    </>
                                )}
                            </li>
                            <li className="text-center">
                                {formatRefreshedBefore(lastSeenBridgeXDevice.lastSeen)}
                            </li>
                        </ul>
                        {tripProgress && (
                            <div className="w-full mt-4">
                                <div className="w-full h-4 bg-gray-200 rounded-full dark:bg-gray-700 relative">
                                    <div
                                        className="h-4 bg-blue-500 rounded-full transition-all duration-500 ease-out flex items-center justify-center"
                                        style={{ width: `${tripProgress}%` }}>
                                        {tripProgress > TRIP_PROGRESS_THRESHOLD_PERCENT && (
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
                    events={(places && trip?.getCalendarEvents(day, places, timezone) as (Flight | (Place & PlaceDate))[]) ?? null}
                    stay={trip?.getStay(day, configuration?.homeLocation?.timezone)}
                    fitness={trip?.fitness?.[(day.getTime() - startOfDay(fromUnixTime(trip?.start ?? 0)).getTime()) / (ONE_DAY_SECONDS * 1000)]}
                    noteSelector={prefix => trip?.notes?.filter(note => note.content.startsWith(prefix))?.map(note => ({ ...note, content: note.content.substring(prefix.length) })) ?? []}
                    publicHoliday={trip?.getPublicHoliday(day)}
                    timezone={timezone}
                    displayWarnings={displayWarnings}
                    onNoteAdded={onNoteAdded}
                    onNoteRemoved={onNoteRemoved}
                    onPhotosAdded={onPhotosAdded} />
            ))?.filter(Boolean)?.slice(0, dayCardsCount)}
            <button
                onClick={() => setTimezone(previous => previous ? undefined : configuration?.homeLocation?.timezone)}
                className="absolute bottom-5 right-5 btn-chip">
                {timezone ? (
                    <Earth size={16} />
                ) : (
                    <House size={16} />
                )}
            </button>
        </div>
    )
}
