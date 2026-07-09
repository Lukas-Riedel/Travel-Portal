import { eachDayOfInterval, fromUnixTime, startOfDay } from "date-fns"
import DayCard from "./DayCard.tsx"
import { useConfiguration } from "../contexts/ConfigContext"
import { useEffect, useMemo, useState } from "react"
import { ArrowRightLeft, Calendar, Copy, Earth, House, Upload } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import CardGrid from "./CardGrid.tsx"
import { fromZonedTime, toZonedTime } from "date-fns-tz"
import { useUserInput } from "../hooks/useUserInput.tsx"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { useTranslation } from "react-i18next"
import { formatTimestamp, ONE_DAY_SECONDS } from "../utils/timeUtils.ts"

// TODO: Reconsider displayCopyItineraryButton. Individual sections should be included based on whether they're empty or not (which will actually be more robust than checking one specific role, because if the user doesn't has access to, e.g., trip notes, the array will always be empty).
export default function TripCalendar({ trip, places, tripCandidates, displayWarnings, displayCopyItineraryButton, onTripMoved, onTripLoaded, onPhotosAdded, onNoteAdded, onNoteRemoved }) {
    const { t } = useTranslation()
    const { configuration } = useConfiguration()
    const { showMoveTripToast, showLoadTripToast, showCopyTripItineraryToast } = usePredefinedUserInput()

    const [timezone, setTimezone] = useState(undefined)

    const days = useMemo(() => trip && eachDayOfInterval({
        start: startOfDay(toZonedTime(fromUnixTime(trip?.start || (places && Math.min(...places.flatMap(place => place?.dates).map(date => date.start)))), timezone)),
        end: startOfDay(toZonedTime(fromUnixTime(trip?.end || (places && Math.max(...places.flatMap(place => place?.dates).map(date => date.end)))) - 1, timezone))
    }), [timezone, trip, places])


    const handleMoved = () => {
        showMoveTripToast(start => onTripMoved(Math.round(fromZonedTime(start.toISOString().slice(0, -1), configuration?.homeLocation?.timezone).getTime() / 1000)))
    }

    const handleLoaded = () => {
        showLoadTripToast(tripCandidates ?? [], onTripLoaded)
    }

    const handleTripItineraryCopied = () => {
        const getListAsString = (items) => items.map(item => `- ${item}`).join("\n")

        // TODO: This is partially duplicated in DayCard.
        const convertedPlaces = (places ?? []).flatMap(place => place.dates.map(date => ({ text: `${place.name} (${formatTimestamp(date.start, t("general.format.datetime.year.excluded"), place.timezone)} - ${formatTimestamp(date.end, t("general.format.datetime.year.excluded"), place.timezone)})`, start: date.start }))).sort((a, b) => a.start - b.start).map(item => item.text)
        const convertedFlights = [...(trip.flights ?? []), ...(trip.watchedFlights ?? [])].map(flight => `${flight.from.shortName} - ${flight.to.shortName} (${formatTimestamp(flight.start, t("general.format.datetime.year.excluded"), flight.from.timezone)} - ${formatTimestamp(flight.end, t("general.format.datetime.year.excluded"), flight.to.timezone)})`)
        const convertedStays = (trip.stays ?? []).map(stay => `${stay.address} (${formatTimestamp(stay.start, t("general.format.date.year.excluded"))} - ${formatTimestamp(stay.end - ONE_DAY_SECONDS, t("general.format.date.year.excluded"))})`)
        const convertedNotes = (trip.notes ?? []).map(note => note.content)

        // TODO: Do not include individual sections if the array is empty. Do not provide the format in the i18n file.
        const itinerary = t("trip.itinerary", {
            places: getListAsString(convertedPlaces),
            flights: getListAsString(convertedFlights),
            stays: getListAsString(convertedStays),
            notes: getListAsString(convertedNotes)
        })

        showCopyTripItineraryToast(() => navigator.clipboard.writeText(itinerary))
    }

    return (
        <div className="relative w-full my-4">
            <CardGrid rowSize={4}>
                {days?.map((day, index) => (
                    <DayCard
                        key={index}
                        day={day}
                        events={places && trip.getCalendarEvents(day, places, timezone)}
                        stay={trip.getStay(day, configuration?.homeLocation?.timezone)}
                        noteSelector={prefix => trip?.notes?.filter(note => note.content.startsWith(prefix))?.map(note => ({ ...note, content: note.content.substring(prefix.length) }))}
                        fitness={trip.fitness && trip.fitness[index]}
                        publicHoliday={trip.getPublicHoliday(day)}
                        timezone={timezone}
                        displayWarnings={displayWarnings}
                        onNoteAdded={onNoteAdded}
                        onNoteRemoved={onNoteRemoved}
                        onPhotosAdded={onPhotosAdded} />
                ))}
            </CardGrid>
            <div className="absolute bottom-3 right-3 flex items-center gap-2 z-50">
                {displayCopyItineraryButton && (
                    <button
                        onClick={handleTripItineraryCopied}
                        className="btn-chip-gray">
                        <Copy size={16} />
                    </button>
                )}
                {onTripMoved && (
                    <button
                        onClick={handleMoved}
                        className="btn-chip-gray">
                        <ArrowRightLeft size={16} />
                    </button>
                )}
                {onTripLoaded && (
                    <button
                        onClick={handleLoaded}
                        className="btn-chip-gray">
                        <Upload size={16} />
                    </button>
                )}
                {onTripMoved && (
                    <button
                        onClick={() => window.open((d => `https://calendar.google.com/calendar/u/0/r/week/${d.getFullYear()}/${d.getMonth() + 1}/${d.getDate()}`)(fromUnixTime(trip.start)), "_blank")}
                        className="btn-chip-gray">
                        <Calendar size={16} />
                    </button>
                )}
                <button
                    onClick={() => setTimezone(prev => prev ? undefined : configuration?.homeLocation?.timezone)}
                    className="btn-chip-gray">
                    {timezone ? <House size={16} /> : <Earth size={16} />}
                </button>
            </div>
        </div>
    )
}
