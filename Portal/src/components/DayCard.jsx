import { format, fromUnixTime } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import { cs } from 'date-fns/locale'
import { formatDuration, formatSteps, formatKilometers } from "../utils/formatters"
import { Bed, Footprints, PartyPopper, CircleHelp, Sunrise, Sunset, Sun, Cloud, CloudSun, CloudFog, CloudRain, CloudLightning, Snowflake, CloudHail, CloudDrizzle, PlaneTakeoff, MapPin, ImagePlus, Plane, Upload, OctagonAlert, NotebookPen, Trash, Trash2, Plus } from "lucide-react"
import { getPrettyName } from "../utils/helpers"
import { Link } from "react-router-dom"
import React, { useEffect, useMemo, useState } from "react"
import { TailSpin } from "react-loader-spinner"
import Tooltip from "./Tooltip"
import showFormToast from "./FormToast"
import { useAuth } from "../contexts/AuthContext"
import { useDevices } from "../hooks/useDevices"
import showConfirmToast from "./ConfirmToast"
import showInputToast from "./InputToast"

const weatherIcons = {
    "clearsky": Sun,
    "cloudy": Cloud,
    "fair": Sun,
    "fog": CloudFog,
    "heavyrain": CloudRain,
    "heavyrainandthunder": CloudLightning,
    "heavyrainshowers": CloudRain,
    "heavyrainshowersandthunder": CloudLightning,
    "heavysleet": CloudHail,
    "heavysleetandthunder": CloudLightning,
    "heavysleetshowersandthunder": CloudLightning,
    "heavysnow": CloudHail,
    "heavysnowandthunder": CloudLightning,
    "heavysnowshowers": CloudLightning,
    "heavysnowshowersandthunder": CloudLightning,
    "lightrain": CloudDrizzle,
    "lightrainandthunder": CloudLightning,
    "lightrainshowers": CloudDrizzle,
    "lightrainshowersandthunder": CloudLightning,
    "lightsleet": CloudHail,
    "lightsleetandthunder": CloudLightning,
    "lightsleetshowers": CloudHail,
    "lightsleetshowersandthunder": CloudLightning,
    "lightsnow": Snowflake,
    "lightsnowandthunder": CloudLightning,
    "lightsnowshowers": Snowflake,
    "lightsnowshowersandthunder": CloudLightning,
    "partlycloudy": CloudSun,
    "rain": CloudRain,
    "rainandthunder": CloudLightning,
    "rainshowers": CloudRain,
    "rainshowersandthunder": CloudLightning,
    "sleet": CloudHail,
    "sleetandthunder": CloudLightning,
    "sleetshowers": CloudHail,
    "sleetshowersandthunder": CloudLightning,
    "snow": Snowflake,
    "snowandthunder": CloudLightning,
    "snowshowers": Snowflake,
    "snowshowersandthunder": CloudLightning
}

const sunAltitudeThreshold = 20
const agentOnlineStatusThresholdSeconds = 60

export default function DayCard({ day, events, stay, fitness, noteSelector, publicHoliday, timezone, onPhotosAdded, onNoteRemoved, onNoteAdded }) {
    const { isAdmin } = useAuth()
    const agents = useDevices({ type: "agent" })

    const isToday = useMemo(() => new Date().toDateString() === day?.toDateString(), [day])

    const notePrefix = useMemo(() => format(day, "d.M.", { locale: cs }) + " ", [day])
    const notes = useMemo(() => noteSelector && noteSelector(notePrefix), [noteSelector, notePrefix])

    const handleNoteRemoved = noteId => {
        showConfirmToast(
            "Opravdu chceš odstranit vybranou poznámku?",
            "Poznámka byla úspěšně odstraněna",
            "Nepodařilo se odstranit poznámku",
            async () => onNoteRemoved(noteId)
        )
    }

    const handleNoteCreated = () => {
        showInputToast(
            "Zadej obsah poznámky:",
            "",
            "Poznámka byla úspěšně přidána",
            "Nepodařilo se přidat poznámku",
            async description => onNoteAdded(notePrefix + description)
        )
    }

    const renderDescriptionRow = (color, items) => items?.length > 0 && (
        <div className={`flex items-center text-xs ${color} space-x-1`}>
            {items.filter(Boolean).map((value, index) => (
                <React.Fragment key={index}>
                    {index > 0 && <span>•</span>}
                    <span>{value}</span>
                </React.Fragment>
            ))}
        </div>
    )

    const formatTimestamp = (timestamp, timestampTimezone) => format(toZonedTime(fromUnixTime(timestamp), timezone || timestampTimezone), "HH:mm")

    const handlePhotosAdded = (placeId, placeName, albumId, timestamp) => {
        showFormToast(
            "Zadej cestu k fotkám k nahrání:",
            [
                { label: "Cesta", required: true },
                { label: "Pozice hlavní fotky", required: false, type: "number", min: 1 },
                { label: "Agent", required: true, type: "select", options: agents.filter(agent => agent.lastSeen + agentOnlineStatusThresholdSeconds > Date.now() / 1000).map(agent => ({ id: agent.id, name: agent.name })) }
            ],
            "Nahrávání fotek bude brzy zahájeno",
            "Při nahrávání fotek došlo k chybě",
            async (path, mainPhotoPosition, agentId) => onPhotosAdded(agentId, placeId, placeName, albumId, timestamp, path, mainPhotoPosition)
        )
    }

    const requiresAttention = event => isAdmin && event.sun?.altitude && (event.sun.altitude.start < sunAltitudeThreshold || event.sun.altitude.end < sunAltitudeThreshold)

    return (day && events) ? ((events.length > 0 || stay) && (
        <div className={`rounded-xl p-4 h-full flex flex-col ${isToday ? "bg-gray-100 border border-gray-400 text-gray-900 shadow-lg" : "shadow-md bg-white"}`}>
            <div className="mb-4">
                <div className="flex justify-between items-start">
                    <span
                        className={`font-bold whitespace-nowrap leading-none ${isAdmin && onNoteAdded ? "hover:underline hover:text-gray-600 hover:cursor-pointer transition-colors duration-200" : ""}"`}
                        onClick={isAdmin && onNoteAdded && handleNoteCreated}>
                        {day?.getFullYear() > 1970 ? format(day, "EEE d.M.", { locale: cs }) : `Den ${Math.floor(day.getTime() / (1000 * 60 * 60 * 24)) + 2}`}
                    </span>
                    {stay && (
                        <div className="flex flex-col items-end min-w-0 ml-4">
                            <a
                                href={`https://www.google.com/maps/search/${stay.address}`}
                                className="text-amber-900 text-xs leading-tight text-right break-words hover:underline hover:text-amber-600 transition-colors duration-200">
                                <Bed
                                    size={14}
                                    className="inline mr-1 relative top-[-1px]" />
                                <span className="whitespace-nowrap mr-1">
                                    {stay.name.split(" ")[0]}
                                </span>
                                {stay.name.split(" ").slice(1).join(" ")}
                            </a>
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
                            {event.from && event.to && (
                                <>
                                    <span className="text-sky-600">
                                        <PlaneTakeoff size={16} />
                                    </span>
                                    <span className="font-medium text-sky-600">
                                        {formatTimestamp(event.start, event.from.timezone)}
                                    </span>
                                    <span className="whitespace-nowrap text-sky-600">
                                        {event.from.id ? (
                                            <Link
                                                to={`/airport/${event.from.id}`}
                                                className="hover:underline hover:text-sky-300 transition-colors duration-200">
                                                {event.from.shortName}
                                            </Link>
                                        ) : (
                                            <a
                                                href={`https://www.google.com/maps/search/Letiště ${event.from.shortName}`}
                                                className="hover:underline hover:text-sky-300 transition-colors duration-200">
                                                {event.from.shortName}
                                            </a>
                                        )}
                                        {` → `}
                                        {event.to.id ? (
                                            <Link
                                                to={`/airport/${event.to.id}`}
                                                className="hover:underline hover:text-sky-300 transition-colors duration-200">
                                                {event.to.shortName}
                                            </Link>
                                        ) : (
                                            <a
                                                href={`https://www.google.com/maps/search/Letiště ${event.to.shortName}`}
                                                className="hover:underline hover:text-sky-300 transition-colors duration-200">
                                                {event.to.shortName}
                                            </a>
                                        )}
                                    </span>
                                </>
                            )}
                            {event.name && (
                                <>
                                    <span className={requiresAttention(event) ? "text-red-600" : "text-indigo-600"}>
                                        <MapPin size={16} />
                                    </span>
                                    <div className={`font-medium ${requiresAttention(event) ? "text-red-600" : "text-indigo-600"} relative group inline-block`}>
                                        {formatTimestamp(event.start, event.timezone)}
                                        {event.sun?.altitude && (
                                            <Tooltip>
                                                <Sun size={16} />
                                                {`Výška slunce ${(event.sun.altitude.start).toFixed(1)}°`}
                                            </Tooltip>
                                        )}
                                    </div>
                                    <Link
                                        to={`${(window.location.pathname.startsWith("/plan") ? "/plan" : "")}/place/${event.id}`}
                                        className={`${requiresAttention(event) ? "text-red-600" : "text-indigo-600"} hover:underline ${requiresAttention(event) ? "hover:text-red-300" : "hover:text-indigo-300"} transition-colors duration-200`}>
                                        {getPrettyName(event.name)}
                                    </Link>
                                    {isAdmin && event.sun?.altitude && (event.sun.altitude.start < sunAltitudeThreshold || event.sun.altitude.end < sunAltitudeThreshold) && (
                                        <span className={requiresAttention(event) ? "text-red-600" : "text-indigo-600"}>
                                            <OctagonAlert size={16} />
                                        </span>
                                    )}
                                    {onPhotosAdded && isAdmin && (
                                        <button className={requiresAttention(event) ? "btn-icon-hover text-red-600" : "btn-icon-hover text-indigo-600"}
                                            onClick={() => handlePhotosAdded(event.id, event.name, event.album?.id, event.start)}>
                                            <ImagePlus size={16} />
                                        </button>
                                    )}
                                </>
                            )}
                        </div>
                        {event.from && event.to && renderDescriptionRow("text-sky-600", [
                            formatDuration(event.end - event.start),
                            event.flight && (
                                <a
                                    href={`https://www.flightradar24.com/data/flights/${event.flight}`}
                                    className="hover:underline hover:text-sky-300 transition-colors duration-200">
                                    {event.flight}
                                </a>
                            ),
                            event.aircraft && (
                                <div className="relative group inline-block">
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
                        {event.weather && (() => {
                            const WeatherIcon = weatherIcons[event.weather.symbol] ?? CircleHelp

                            return (
                                <div className="relative group inline-block">
                                    {renderDescriptionRow("text-amber-600", [
                                        event.weather.temperature && (
                                            <div className="flex items-center space-x-1">
                                                <WeatherIcon className="w-4 h-4 mr-1 shrink-0" />
                                                <span>
                                                    {event.weather.temperature.toFixed(1) + " °C"}
                                                </span>
                                            </div>
                                        ),
                                        event.weather.precipitation != null && event.weather.precipitation.toFixed(1) + " mm",
                                        event.weather.clouds != null && Math.round(event.weather.clouds) + " %",
                                        event.weather.wind != null && event.weather.wind.toFixed(0) + " m/s"
                                    ])}
                                    {event.weather.symbol && (
                                        <Tooltip>
                                            <Sun size={16} />
                                            {`Poslední aktualizace v ${format(fromUnixTime(event.weather.lastUpdate), "HH:mm")}`}
                                        </Tooltip>
                                    )}
                                </div>
                            )
                        })()}
                        {isAdmin && event.album?.uploadingStart && event.album?.uploadingProgress && (
                            <div className="relative group inline-block">
                                {renderDescriptionRow("text-yellow-500", [
                                    (
                                        <>
                                            <div className="flex items-center space-x-1">
                                                <Upload className="w-4 h-4 mr-1 shrink-0" />
                                                <RemainingUploadTime album={event.album} />
                                            </div>
                                            <Tooltip>
                                                <Upload size={16} />
                                                Nahrávání probíhá... ({event.album.uploadingProgress} %)
                                            </Tooltip>
                                        </>
                                    )
                                ])}
                            </div>
                        )}
                    </li>
                ))}
            </ul>
            {isAdmin && notes?.length > 0 && (
                <ul className="mt-3 text-xs text-gray-500 space-y-2 leading-5">
                    {notes.map(note => (
                        <li
                            key={note.id}
                            className="clear-left flex items-center space-x-2">
                            <NotebookPen className="w-4 h-4 shrink-0" />
                            <div className="relative group inline-block flex-1 min-w-0">
                                <span
                                    className="truncate block"
                                    dangerouslySetInnerHTML={{ __html: note.content }} />
                                <Tooltip>
                                    <NotebookPen size={16} />
                                    <span dangerouslySetInnerHTML={{ __html: note.content }} />
                                </Tooltip>
                            </div>
                            {onNoteRemoved && (
                                <button
                                    className="btn-icon-hover"
                                    onClick={() => handleNoteRemoved(note.id)}>
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
                        fitness.distance && formatKilometers((Math.round(fitness.distance) / 1000).toFixed(1)),
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
            {events.some(event => event.sun) && (
                <div className="mt-3 flex justify-between items-center">
                    <div className="flex items-center space-x-1 text-cyan-400">
                        <Sunrise
                            className="mr-1"
                            size={16} />
                        <span>
                            {events.filter(event => event.sun?.sunrise).map(event => formatTimestamp(event.sun.sunrise, event.timezone))[0]}
                        </span>
                    </div>
                    <div className="flex items-center space-x-1 text-rose-400">
                        <Sunset
                            className="mr-1"
                            size={16} />
                        <span>
                            {events.filter(event => event.sun?.sunset).map(event => formatTimestamp(event.sun.sunset, event.timezone)).at(-1)}
                        </span>
                    </div>
                </div>
            )}
        </div>
    )) : (
        <div className="fbg-white rounded-xl shadow p-4 flex flex-col items-center justify-center h-[150px]">
            <TailSpin
                color="black"
                height={30}
                width={30} />
        </div>
    )
}

function RemainingUploadTime({ album }) {
    const computedRemaining = useMemo(() => {
        if (album.uploadingProgress > 0 && album.uploadingProgress < 100) {
            const now = Date.now() / 1000
            const elapsed = now - album.uploadingStart
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

    return remaining !== null ? (
        <span>
            Zbývá {formatDuration(Math.max(0, remaining), true)}
        </span>
    ) : (
        <span>
            Nahrávání bude brzy dokončeno
        </span>
    )
}