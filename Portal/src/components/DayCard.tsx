import { useEffect, useMemo, useState } from "react"
import { format, isFuture, isToday } from "date-fns"
import { Bed, Footprints, PartyPopper, PlaneTakeoff, MapPin, ImagePlus, Plane, Upload, OctagonAlert, NotebookPen, Trash2, Ship, Sunrise, Sunset } from "lucide-react"
import ReactMarkdown from "react-markdown"
import { useTranslation } from "react-i18next"
import Tooltip from "./Tooltip.jsx"
import { useDevices } from "../hooks/useDevices.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { type Stay, type Fitness, type PublicHoliday, type Note, type Flight, type Place, type Date as PlaceDate, DeviceType, type Album, type TripIdentifier } from "../types/CoreSwaggerTypes.ts"
import { getEntityPrettyName } from "../utils/formattingUtils.ts"
import { useFormatters } from "../hooks/useFormatters.ts"
import WeatherSummary from "./WeatherSummary.tsx"
import { formatTimestamp, getCurrentTimestamp, getDayIndex } from "../utils/timeUtils.ts"
import { getSunAltitude, getSunrise, getSunset } from "../utils/sunUtils.ts"
import { isDeviceOnline } from "../utils/deviceUtils.ts"
import { useLocale } from "../hooks/useLocale.ts"
import LoadingCard from "./LoadingCard.tsx"
import { getFlightLink, getMapLink, getSatelliteLink } from "../utils/navigationUtils.ts"
import AppLink from "./AppLink.tsx"
import Card from "./Card.tsx"

const SUN_ALTITUDE_THRESHOLD = 20
const PRECIPITATION_PROBABILITY_THRESHOLD = 50
const PRECIPITATION_TOTAL_THRESHOLD = 0.1
const DEFAULT_PATH_PREFIX = "/mnt/photos"

type DayEvent = Flight | (Place & PlaceDate)

const isFlight = (event: DayEvent): event is Flight => "from" in event && "to" in event
const isPlace = (event: DayEvent): event is Place & PlaceDate => "name" in event && !("from" in event)

interface DayCardProps {
    day: globalThis.Date | null
    events: DayEvent[] | null
    stay?: Stay
    fitness?: Fitness
    publicHoliday?: PublicHoliday
    timezone?: string
    displayWarnings?: boolean
    noteSelector?: (prefix: string) => Note[]
    onPhotosAdded?: (agentId: string, placeId: string, placeName: string, path: string, sendNotification: boolean, albumId?: string, timestamp?: number, mainPhotoPosition?: number) => Promise<void>
    onNoteRemoved?: (noteId: string) => Promise<void>
    onNoteAdded?: (content: string) => Promise<Note>
}

export default function DayCard({ day, events, stay, fitness, publicHoliday, timezone, displayWarnings, noteSelector, onPhotosAdded, onNoteRemoved, onNoteAdded }: DayCardProps) {
    const { t } = useTranslation()
    const locale = useLocale()
    const agents = useDevices({ type: DeviceType.Agent })
    const { formatDuration, formatSteps, formatKilometers } = useFormatters()
    const { showCreateNoteToast, showRemoveNoteToast, showUploadPhotosToast } = usePredefinedUserInput()

    const notePrefix = useMemo(() => day ? `${format(day, t("general.format.date.year.excluded"), { locale: locale })} ` : "", [day])
    const notes = useMemo(() => noteSelector && notePrefix ? noteSelector(notePrefix) : [], [noteSelector, notePrefix])

    const isExactDate = useMemo(() => day && day.getFullYear() > 1970, [day])

    const doFormatTimestamp = (timestamp: number, timestampTimezone: string) => formatTimestamp(timestamp, t("general.format.time"), timezone || timestampTimezone)

    const sunriseTime = useMemo(() => {
        if (!events) {
            return null
        }

        const sunrise = events.filter(event => isPlace(event)).filter(event => event.start > getCurrentTimestamp()).map(event => {
            const sunriseTimestamp = getSunrise(event.start, event)
            if (!sunriseTimestamp) {
                return null
            }

            return doFormatTimestamp(sunriseTimestamp, event.timezone)
        }).filter(Boolean)[0]

        return sunrise
    }, [events, doFormatTimestamp])

    const sunsetTime = useMemo(() => {
        if (!events) {
            return null
        }

        const sunset = events.filter(event => isPlace(event)).filter(event => event.start > getCurrentTimestamp()).map(event => {
            const sunsetTimestamp = getSunset(event.start, event)
            if (!sunsetTimestamp) {
                return null
            }

            return doFormatTimestamp(sunsetTimestamp, event.timezone)
        }).filter(Boolean).at(-1)

        return sunset
    }, [events, doFormatTimestamp])

    const dayLabel = useMemo(() => {
        if (!day) {
            return null
        }

        return isExactDate
            ? format(day, t("general.format.day.year.excluded"), { locale: locale })
            : `${t("general.label.day")} ${getDayIndex(day)}`
    }, [day, t, locale, isExactDate])

    const shouldShowButtons = useMemo(() => isExactDate && onNoteAdded, [isExactDate, onNoteAdded])

    const handleNoteRemoved = (note: Note) => {
        if (onNoteRemoved) {
            showRemoveNoteToast(() => onNoteRemoved(note.id))
        }
    }

    const handleNoteCreated = () => {
        if (onNoteAdded) {
            showCreateNoteToast((content: string) => onNoteAdded(notePrefix + content))
        }
    }

    const handlePhotosAdded = (placeId: string, placeName: string, albumId?: string, timestamp?: number, sendNotification?: boolean, trip?: TripIdentifier) => {
        if (onPhotosAdded) {
            const onlineAgents = agents.filter(agent => isDeviceOnline(agent))
            showUploadPhotosToast(onlineAgents, (path: string, agentId: string, sendNotification: boolean, mainPhotoPosition?: number) =>
                onPhotosAdded(agentId, placeId, placeName, path, sendNotification, albumId, timestamp, mainPhotoPosition), sendNotification, trip && timestamp && `${DEFAULT_PATH_PREFIX}/${trip.year}/${trip.name} ${trip.year}/${placeName} ${formatTimestamp(timestamp, t("general.format.date.year.included"))}`)
        }
    }

    const renderDescriptionRow = (color: string, items: (string | React.ReactElement | false | null | undefined)[]) => {
        const filteredItems = items.filter(Boolean)
        return filteredItems.length > 0 && (
            <div className={`flex items-center text-xs ${color} space-x-1`}>
                {filteredItems.map((value, index) => (
                    <span key={index}>
                        {index > 0 && <span> • </span>}
                        <span>
                            {value}
                        </span>
                    </span>
                ))}
            </div>
        )
    }

    const requiresAttention = (event: DayEvent) => {
        if (!displayWarnings || event.start <= getCurrentTimestamp()) {
            return false
        }

        if (isFlight(event)) {
            if (!event.flight) {
                return true
            }
        }

        if (isPlace(event)) {
            if (getSunAltitude(event.start, event) < SUN_ALTITUDE_THRESHOLD ||
                getSunAltitude(event.end, event) < SUN_ALTITUDE_THRESHOLD) {
                return true
            }

            if (event.weather?.some(w => w.precipitation?.probability > PRECIPITATION_PROBABILITY_THRESHOLD && w.precipitation?.total > PRECIPITATION_TOTAL_THRESHOLD)) {
                return true
            }
        }

        return false
    }

    const getColor = (event: DayEvent, color: string) => requiresAttention(event) ? "text-red-600" : color
    const getHoverColor = (event: DayEvent, hoverColor: string) => requiresAttention(event) ? "hover:text-red-300" : hoverColor

    if (!day || !events) {
        return (
            <LoadingCard />
        )
    }

    // TODO: Split to smaller components (like WeatherSummary)?
    return (events.length > 0 || stay) && (
        <Card className={`h-full flex flex-col ${day && isToday(day) && "bg-gray-100 border border-gray-400 text-gray-900 shadow-lg"}`}>
            <div className="mb-4">
                <div className="flex justify-between items-start">
                    <div className="group relative inline-flex items-center shrink-0">
                        {dayLabel && (
                            <span className={`font-bold whitespace-nowrap leading-none transition-opacity duration-200 text-gray-900 ${shouldShowButtons && "group-hover:opacity-0"}`}>
                                {dayLabel}
                            </span>
                        )}
                        {shouldShowButtons && (
                            <div className="absolute inset-0 flex items-center space-x-1 opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto transition-opacity duration-200">
                                {onNoteAdded && (
                                    <button
                                        onClick={handleNoteCreated}
                                        className="p-1 btn-icon-hover" >
                                        <NotebookPen size={15} />
                                    </button>
                                )}
                            </div>
                        )}
                    </div>
                    {stay && (
                        <div className="flex flex-col items-end min-w-0 ml-4">
                            {stay.address ? (
                                <a
                                    href={getMapLink(stay.address)}
                                    className="text-amber-900 text-xs leading-tight text-right break-words hover:underline hover:text-amber-600 transition-colors duration-200"
                                    target="_blank"
                                    rel="noopener noreferrer">
                                    <Bed
                                        size={14}
                                        className="inline mr-1 relative top-[-1px]" />
                                    <span className="whitespace-nowrap mr-1">
                                        {stay.name.split(" ")[0]}
                                        <span className="no-underline text-[0px]"> </span>
                                    </span>
                                    {stay.name.split(" ").slice(1).join(" ")}
                                </a>
                            ) : (
                                <div className="text-cyan-600 text-xs leading-tight text-right break-words">
                                    <Ship
                                        size={14}
                                        className="inline mr-1 relative top-[-1px]" />
                                    <span className="whitespace-nowrap mr-1">
                                        {stay.name.split(" ")[0]}
                                        <span className="no-underline text-[0px]"> </span>
                                    </span>
                                    {stay.name.split(" ").slice(1).join(" ")}
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
            <ul className="flex-1 space-y-2">
                {events.map((event, index) => (
                    <li
                        key={index}
                        className="space-y-1">
                        <div className="flex items-center space-x-2">
                            {isFlight(event) && (
                                <>
                                    <span className={getColor(event, "text-sky-600")}>
                                        <PlaneTakeoff size={16} />
                                    </span>
                                    <span className={`font-medium ${getColor(event, "text-sky-600")}`}>
                                        {doFormatTimestamp(event.start, event.from.timezone)}
                                    </span>
                                    <span className={`whitespace-nowrap ${getColor(event, "text-sky-600")}`}>
                                        {event.from.id ? (
                                            <AppLink
                                                to={event.from}
                                                className={`hover:underline ${getHoverColor(event, "hover:text-sky-300")} transition-colors duration-200`}>
                                                {event.from.shortName}
                                            </AppLink>
                                        ) : (
                                            <a
                                                href={getMapLink(t("airport.format", { name: event.from.shortName }))}
                                                className={`hover:underline ${getHoverColor(event, "hover:text-sky-300")} transition-colors duration-200`}
                                                target="_blank"
                                                rel="noopener noreferrer">
                                                {event.from.shortName}
                                            </a>
                                        )}
                                        {" → "}
                                        {event.to.id ? (
                                            <AppLink
                                                to={event.to}
                                                className={`hover:underline ${getHoverColor(event, "hover:text-sky-300")} transition-colors duration-200`}>
                                                {event.to.shortName}
                                            </AppLink>
                                        ) : (
                                            <a
                                                href={getMapLink(t("airport.format", { name: event.to.shortName }))}
                                                className={`hover:underline ${getHoverColor(event, "hover:text-sky-300")} transition-colors duration-200`}
                                                target="_blank"
                                                rel="noopener noreferrer">
                                                {event.to.shortName}
                                            </a>
                                        )}
                                    </span>
                                    {requiresAttention(event) && (
                                        <span className="text-red-600">
                                            <OctagonAlert size={16} />
                                        </span>
                                    )}
                                </>
                            )}
                            {isPlace(event) && (
                                <>
                                    <a
                                        href={getSatelliteLink(event)}
                                        className={getColor(event, "text-indigo-600")}
                                        target="_blank"
                                        rel="noopener noreferrer">
                                        <MapPin size={16} />
                                    </a>
                                    <div className={`font-medium ${getColor(event, "text-indigo-600")} relative group inline-block`}>
                                        {doFormatTimestamp(event.start, event.timezone)}
                                    </div>
                                    <AppLink
                                        to={event}
                                        className={`${getColor(event, "text-indigo-600")} hover:underline ${getHoverColor(event, "hover:text-indigo-300")} transition-colors duration-200`}>
                                        {getEntityPrettyName(event.name)}
                                    </AppLink>
                                    {requiresAttention(event) && (
                                        <span className="text-red-600">
                                            <OctagonAlert size={16} />
                                        </span>
                                    )}
                                    {onPhotosAdded && (
                                        <button
                                            className={`btn-icon-hover ${getColor(event, "text-indigo-600")}`}
                                            onClick={() => handlePhotosAdded(event.id, event.name, event.album?.id, event.start, event.trip !== undefined, event.trip)}>
                                            <ImagePlus size={16} />
                                        </button>
                                    )}
                                </>
                            )}
                        </div>
                        {isFlight(event) && renderDescriptionRow(getColor(event, "text-sky-600"), [
                            formatDuration(event.end - event.start),
                            event.flight && (
                                <a
                                    href={getFlightLink(event.flight)}
                                    className={`hover:underline ${getHoverColor(event, "hover:text-sky-300")} transition-colors duration-200`}
                                    target="_blank"
                                    rel="noopener noreferrer">
                                    {event.flight}
                                </a>
                            ),
                            event.aircraft && (
                                <div className="relative group inline-block hover:cursor-help">
                                    {event.aircraft}
                                    {event.registration && (
                                        <Tooltip>
                                            <Plane size={16} />
                                            {event.registration}
                                        </Tooltip>
                                    )}
                                </div>
                            )
                        ])}
                        {isPlace(event) && event.weather?.length > 0 && (
                            <WeatherSummary
                                weather={event.weather}
                                coordinates={event as any}
                                start={event.start}
                                end={event.end}
                                timezone={timezone || event.timezone} />
                        )}
                        {isPlace(event) && onPhotosAdded && event.album?.uploadingStart && event.album?.uploadingProgress && (
                            <div className="relative group inline-block hover:cursor-help">
                                {renderDescriptionRow("text-yellow-500", [
                                    (
                                        <>
                                            <div className="flex items-center space-x-1">
                                                <Upload className="w-4 h-4 mr-1 shrink-0" />
                                                <RemainingUploadTime album={event.album} />
                                            </div>
                                            <Tooltip>
                                                <Upload size={16} />
                                                {t("album.upload.active", { progress: event.album.uploadingProgress })}
                                            </Tooltip>
                                        </>
                                    )
                                ])}
                            </div>
                        )}
                    </li>
                ))}
            </ul>
            {notes && notes.length > 0 && (
                <ul className="mt-3 text-xs text-gray-500 space-y-2 leading-5">
                    {notes.map(note => (
                        <li
                            key={note.id}
                            className="clear-left flex items-center space-x-2">
                            <NotebookPen className="w-4 h-4 shrink-0" />
                            <div className="relative group inline-block flex-1 min-w-0 hover:cursor-help">
                                <span className="truncate block">
                                    <ReactMarkdown>
                                        {note.content}
                                    </ReactMarkdown>
                                </span>
                                <Tooltip>
                                    <NotebookPen size={16} />
                                    <ReactMarkdown>
                                        {note.content}
                                    </ReactMarkdown>
                                </Tooltip>
                            </div>
                            {onNoteRemoved && (
                                <button
                                    className="btn-icon-hover"
                                    onClick={() => handleNoteRemoved(note)}>
                                    <Trash2 size={16} />
                                </button>
                            )}
                        </li>
                    ))}
                </ul>
            )}
            {fitness?.steps > 0 && (
                <>
                    <div className="mt-3 flex items-center text-green-600 space-x-1">
                        <Footprints
                            className="mr-1"
                            size={16} />
                        <span>
                            {formatSteps(fitness.steps)}
                        </span>
                    </div>
                    {renderDescriptionRow("text-green-600", [
                        fitness.distance && formatKilometers(Math.round(fitness.distance) / 1000),
                        fitness.seconds && formatDuration(fitness.seconds)
                    ])}
                </>
            )}
            {publicHoliday && (
                <div className="mt-3 flex items-center text-red-500 text-xs space-x-1">
                    <PartyPopper
                        className="mr-1"
                        size={16} />
                    <span>
                        {publicHoliday.name}
                    </span>
                </div>
            )}
            <div className="mt-3 flex justify-between items-center">
                {sunriseTime && (
                    <div className="flex items-center space-x-1 text-cyan-400">
                        <Sunrise
                            className="mr-1"
                            size={16} />
                        <span>
                            {sunriseTime}
                        </span>
                    </div>
                )}
                {sunsetTime && (
                    <div className="flex items-center space-x-1 text-rose-400">
                        <Sunset
                            className="mr-1"
                            size={16} />
                        <span>
                            {sunsetTime}
                        </span>
                    </div>
                )}
            </div>
        </Card>
    )
}

function RemainingUploadTime({ album }: { album: Album }) {
    const { t } = useTranslation()
    const { formatDuration } = useFormatters()

    const computedRemaining = useMemo(() => {
        if (album.uploadingProgress > 0 && album.uploadingProgress < 100) {
            const elapsed = getCurrentTimestamp() - album.uploadingStart
            return elapsed * 100 / album.uploadingProgress - elapsed
        }
        return null
    }, [album.uploadingProgress, album.uploadingStart])

    const [remaining, setRemaining] = useState(computedRemaining)

    useEffect(() => {
        setRemaining(computedRemaining)
    }, [computedRemaining])

    useEffect(() => {
        const interval = setInterval(() => setRemaining(prev => !prev ? prev : Math.max(0, prev - 1)), 1000)
        return () => clearInterval(interval)
    }, [])

    return remaining ? (
        <span>
            {t("album.upload.remaining", { duration: formatDuration(Math.max(0, remaining), true) })}
        </span>
    ) : (
        <span>
            {t("album.upload.finishing")}
        </span>
    )
}