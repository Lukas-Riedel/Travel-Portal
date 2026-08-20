import { eachDayOfInterval, fromUnixTime, startOfDay } from "date-fns"
import DayCard from "./DayCard.tsx"
import { useConfiguration } from "../contexts/ConfigContext"
import { useMemo, useState } from "react"
import { ArrowRightLeft, Calendar, Copy, Earth, House, Upload } from "lucide-react"
import CardGrid from "./CardGrid.tsx"
import { fromZonedTime, toZonedTime } from "date-fns-tz"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { useTranslation } from "react-i18next"
import { formatTimestamp, getTripDays, ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import type { Trip } from "../classes/Trip.ts"
import type { Place } from "../classes/Place.ts"
import type { Note } from "../types/CoreSwaggerTypes.ts"
import { getGoogleCalendarLink } from "../utils/navigationUtils.ts"

interface TripCalendarProps {
    trip: Trip | null
    places: Place[] | null
    tripCandidates?: Trip[]
    displayWarnings?: boolean
    onTripMoved?: (start: number) => Promise<Trip>
    onTripLoaded?: (tripId: string) => Promise<Trip>
    onPhotosAdded?: (agentId: string, placeId: string, placeName: string, path: string, sendNotification: boolean, albumId?: string, timestamp?: number, mainPhotoPosition?: number) => Promise<void>
    onNoteAdded?: (content: string) => Promise<Note>
    onNoteRemoved?: (noteId: string) => Promise<void>
}

export default function TripCalendar({ trip, places, tripCandidates, displayWarnings, onTripMoved, onTripLoaded, onPhotosAdded, onNoteAdded, onNoteRemoved }: TripCalendarProps) {
    const { t } = useTranslation()
    const { configuration } = useConfiguration()
    const { showMoveTripToast, showLoadTripToast, showCopyTripItineraryToast } = usePredefinedUserInput()

    const [timezone, setTimezone] = useState<string | undefined>(undefined)

    const handleMoved = () => {
        showMoveTripToast(start => onTripMoved(Math.round(fromZonedTime(start.toISOString().slice(0, -1), configuration?.homeLocation?.timezone).getTime() / 1000)))
    }

    const handleLoaded = () => {
        showLoadTripToast(tripCandidates ?? [], onTripLoaded)
    }

    const handleTripItineraryCopied = () => {
        const getItineraryPart = (title: string, items: string[]) => items.length > 0 && `## ${title}\n${items.map(item => `- ${item}`).join("\n")}`

        const convertedPlaces = (places ?? []).flatMap(place => place.dates.map(date => ({ text: `${place.name} (${formatTimestamp(date.start, t("general.format.datetime.year.excluded"), place.timezone)} - ${formatTimestamp(date.end, t("general.format.datetime.year.excluded"), place.timezone)})`, start: date.start }))).sort((a, b) => a.start - b.start).map(item => item.text)
        const convertedFlights = [...(trip.flights ?? []), ...(trip.watchedFlights ?? [])].map(flight => `${flight.from.shortName} - ${flight.to.shortName} (${formatTimestamp(flight.start, t("general.format.datetime.year.excluded"), flight.from.timezone)} - ${formatTimestamp(flight.end, t("general.format.datetime.year.excluded"), flight.to.timezone)})`)
        const convertedStays = (trip.stays ?? []).map(stay => `${stay.address} (${formatTimestamp(stay.start, t("general.format.date.year.excluded"))} - ${formatTimestamp(stay.end - ONE_DAY_SECONDS, t("general.format.date.year.excluded"))})`)
        const convertedNotes = (trip.notes ?? []).map(note => note.content)

        const itineraryParts = [
            getItineraryPart(t("trip.itinerary.places"), convertedPlaces),
            getItineraryPart(t("trip.itinerary.flights"), convertedFlights),
            getItineraryPart(t("trip.itinerary.stays"), convertedStays),
            getItineraryPart(t("trip.itinerary.notes"), convertedNotes)
        ]

        showCopyTripItineraryToast(() => navigator.clipboard.writeText(itineraryParts.filter(Boolean).join("\n\n")))
    }

    return (
        <div className="relative w-full my-4">
            <CardGrid rowSize={4}>
                {getTripDays(trip, places, timezone)?.map((day, index) => (
                    <DayCard
                        key={day.getTime()}
                        day={day}
                        events={places && trip?.getCalendarEvents(day, places, timezone)}
                        stay={trip?.getStay(day, configuration?.homeLocation?.timezone)}
                        noteSelector={prefix => trip?.notes?.filter(note => note.content.startsWith(prefix))?.map(note => ({ ...note, content: note.content.substring(prefix.length) }))}
                        fitness={trip?.fitness && trip.fitness[index]}
                        publicHoliday={trip?.getPublicHoliday(day)}
                        timezone={timezone}
                        displayWarnings={displayWarnings}
                        onNoteAdded={onNoteAdded}
                        onNoteRemoved={onNoteRemoved}
                        onPhotosAdded={onPhotosAdded} />
                ))}
            </CardGrid>
            <div className="absolute bottom-3 right-3 flex items-center gap-2 z-50">
                <button
                    onClick={handleTripItineraryCopied}
                    className="btn-chip-gray">
                    <Copy size={16} />
                </button>
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
                        onClick={() => window.open(getGoogleCalendarLink(fromUnixTime(trip.start)), "_blank")}
                        className="btn-chip-gray">
                        <Calendar size={16} />
                    </button>
                )}
                <button
                    onClick={() => setTimezone(previous => previous ? undefined : configuration?.homeLocation?.timezone)}
                    className="btn-chip-gray">
                    {timezone ? (
                        <House size={16} />
                    ) : (
                        <Earth size={16} />
                    )}
                </button>
            </div>
        </div>
    )
}
