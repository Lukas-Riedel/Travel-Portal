import { eachDayOfInterval, fromUnixTime, startOfDay } from "date-fns"
import DayCard from "./DayCard"
import { useConfiguration } from "../contexts/ConfigContext"
import { useEffect, useMemo, useState } from "react"
import { ArrowRightLeft, Calendar, Earth, House, Upload } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import CardGrid from "./CardGrid"
import { fromZonedTime, toZonedTime } from "date-fns-tz"
import { useUserInput } from "../hooks/useUserInput.tsx"

export default function TripCalendar({ trip, places, tripCandidates, onTripMoved, onTripLoaded, onPhotosAdded, onNoteAdded, onNoteRemoved }) {
    const { configuration } = useConfiguration()
    const { isAdmin } = useAuth()
    const { showFormToast } = useUserInput()

    const [timezone, setTimezone] = useState(undefined)

    const days = useMemo(() => trip && eachDayOfInterval({
        start: startOfDay(toZonedTime(fromUnixTime(trip?.start || (places && Math.min(...places.flatMap(place => place?.dates).map(date => date.start)))), timezone)),
        end: startOfDay(toZonedTime(fromUnixTime(trip?.end || (places && Math.max(...places.flatMap(place => place?.dates).map(date => date.end)))) - 1, timezone))
    }), [timezone, trip, places])


    const handleMoved = () => {
        showFormToast(
            "Zadej nový začátek výletu:",
            [
                { type: "date", required: true }
            ],
            async start => onTripMoved(Math.round(fromZonedTime(new Date(start).toISOString().slice(0, -1), configuration?.homeLocation?.timezone).getTime() / 1000)),
            "Výlet byl úspěšně přesunut",
            "Nepodařilo se přesunout výlet"
        )
    }

    const handleLoaded = () => {
        showFormToast(
            "Vyber výlet k načtení:",
            [
                { type: "select", required: true, options: tripCandidates?.map(candidateTrip => ({ id: candidateTrip.id, name: candidateTrip.name })) }
            ],
            onTripLoaded,
            "Výlet byl úspěšně načten",
            "Nepodařilo se načíst výlet"
        )
    }
    return (
        <div className="relative w-full my-4">
            <CardGrid cardsPerRowCount={4}>
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
                        onNoteAdded={onNoteAdded}
                        onNoteRemoved={onNoteRemoved}
                        onPhotosAdded={onPhotosAdded} />
                ))}
            </CardGrid>
            <div className="absolute bottom-3 right-3 flex items-center gap-2 z-50">
                {onTripMoved && isAdmin && (
                    <button
                        onClick={handleMoved}
                        className="btn-chip-gray">
                        <ArrowRightLeft size={16} />
                    </button>
                )}
                {onTripLoaded && isAdmin && (
                    <button
                        onClick={handleLoaded}
                        className="btn-chip-gray">
                        <Upload size={16} />
                    </button>
                )}
                {isAdmin && (
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
