import { eachDayOfInterval, fromUnixTime, startOfDay } from "date-fns"
import DayCard from "./DayCard"
import { useConfiguration } from "../contexts/ConfigContext"
import { useEffect, useMemo, useState } from "react"
import { Earth, House } from "lucide-react"

const loadingDaysCount = 4

export default function TripCalendar({ trip, places, onPhotosAdded }) {
    const configuration = useConfiguration()

    const [timezone, setTimezone] = useState(undefined)
    useEffect(() => {
        setTimezone(configuration?.homeLocation?.timezone)
    }, [configuration])

    const days = useMemo(() => trip && eachDayOfInterval({
        start: startOfDay(fromUnixTime(trip?.start || (places && Math.min(...places.flatMap(place => place?.dates).map(date => date.start))))),
        end: startOfDay(fromUnixTime(trip?.end || (places && Math.max(...places.flatMap(place => place?.dates).map(date => date.end)))) - 1)
    }), [trip, places])

    return (
        <div className="relative w-full my-3">
            <div className="grid grid-cols-[repeat(auto-fill,minmax(13rem,1fr))] gap-4 text-sm w-full">
                {days?.map((day, idx) => (
                    <DayCard
                        key={idx}
                        day={day}
                        events={trip.getEvents(day, places, timezone)}
                        stay={trip.getStay(day)}
                        fitness={trip.fitness[idx]}
                        publicHoliday={trip.getPublicHoliday(day)}
                        timezone={timezone}
                        onPhotosAdded={onPhotosAdded} />
                )) ?? (
                        Array.from({ length: loadingDaysCount }).map((_, idx) => (
                            <DayCard key={idx} />
                        ))
                    )}
            </div>
            <button
                onClick={() => setTimezone(prev => prev ? undefined : configuration?.homeLocation?.timezone)}
                className="absolute bottom-3 right-3 btn-chip-gray">
                {timezone ? <House size={16} /> : <Earth size={16} />}
            </button>
        </div>
    )

}
