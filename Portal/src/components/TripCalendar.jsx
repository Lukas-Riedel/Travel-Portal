import { format, eachDayOfInterval, fromUnixTime, startOfDay } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import DayCard from "./DayCard"
import { useConfiguration } from "../contexts/ConfigContext"
import { useMemo, useState } from "react"
import { Earth, House } from "lucide-react"

const loadingDaysCount = 4

export default function TripCalendar({ trip, places, onPhotosAdded }) {
    const configuration = useConfiguration()

    const [timezone, setTimezone] = useState(undefined)

    const days = useMemo(() => eachDayOfInterval({
        start: startOfDay(fromUnixTime(trip?.start || (places && Math.min(...places.flatMap(place => place?.dates).map(date => date.start))))),
        end: startOfDay(fromUnixTime(trip?.end || (places && Math.max(...places.flatMap(place => place?.dates).map(date => date.end)))) - 1)
    }), [trip])

    const getEvents = day => {
        const dayString = day.toDateString()
        const flightEvents = (trip?.flights ?? [])
            .filter(f => startOfDay(toZonedTime(fromUnixTime(f.start), timezone || f.from.timezone)).toDateString() === dayString)
        const placeEvents = (places ?? []).flatMap(place => place.dates
            .filter(d => startOfDay(toZonedTime(fromUnixTime(d.start), timezone || place.timezone)).toDateString() === dayString)
            .map(date => ({ ...date, ...place }))
        )
        return [...flightEvents, ...placeEvents].sort((a, b) => a.start - b.start)
    }

    const getStay = day => {
        return [...(trip?.stays ?? [])].reverse().find(s => {
            const checkin = startOfDay(fromUnixTime(s.start))
            const checkout = startOfDay(fromUnixTime(s.end) - 86400)
            return day >= checkin && day < checkout
        })
    }

    const getPublicHoliday = day => {
        const key = format(day, "d.M.yyyy")
        return trip.publicHolidays?.find(h => h.date === key)
    }

    return (
        <div className="relative w-full my-3">
            <div className="grid grid-cols-[repeat(auto-fill,minmax(13rem,1fr))] gap-4 text-sm w-full">
                {trip && places ? days.map((day, idx) => (
                    <DayCard
                        key={idx}
                        day={day}
                        events={getEvents(day)}
                        stay={getStay(day)}
                        fitness={trip.fitness[idx]}
                        publicHoliday={getPublicHoliday(day)}
                        timezone={timezone}
                        onPhotosAdded={onPhotosAdded} />
                )) : (
                    Array.from({ length: loadingDaysCount }).map((_, idx) => (
                        <DayCard key={idx} />
                    ))
                )}
            </div>
            <button
                onClick={() => setTimezone(prev => prev ? undefined : configuration?.homeLocation?.timezone)}
                className="absolute bottom-3 right-3 btn-chip-gray">
                {timezone ? <Earth size={16} /> : <House size={16} />}
            </button>
        </div>
    )

}
