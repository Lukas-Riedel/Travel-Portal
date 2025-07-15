import { eachDayOfInterval, fromUnixTime, startOfDay } from "date-fns"
import DayCard from "./DayCard"
import { useConfiguration } from "../contexts/ConfigContext"
import { useEffect, useMemo, useState } from "react"
import { ArrowRightLeft, Earth, House, Upload } from "lucide-react"
import showFormToast from "./FormToast"
import { useAuth } from "../contexts/AuthContext"
import CardGrid from "./CardGrid"

const loadingDaysCount = 4

export default function TripCalendar({ trip, places, tripCandidates, onTripMoved, onTripLoaded, onPhotosAdded }) {
    const configuration = useConfiguration()
    const { isAdmin } = useAuth()

    const [timezone, setTimezone] = useState(undefined)
    useEffect(() => {
        setTimezone(configuration?.homeLocation?.timezone)
    }, [configuration])

    const days = useMemo(() => trip && eachDayOfInterval({
        start: startOfDay(fromUnixTime(trip?.start || (places && Math.min(...places.flatMap(place => place?.dates).map(date => date.start))))),
        end: startOfDay(fromUnixTime(trip?.end || (places && Math.max(...places.flatMap(place => place?.dates).map(date => date.end)))) - 1)
    }), [trip, places])


    const handleMoved = () => {
        showFormToast(
            "Zadej, o kolik dnů se má výlet přesunout:",
            [
                { type: "number", required: true }
            ],
            "Výlet byl úspěšně přesunut",
            "Nepodařilo se přesunout výlet",
            onTripMoved
        )
    }

    const handleLoaded = () => {
        showFormToast(
            "Vyber výlet k načtení:",
            [
                { type: "select", required: true, options: tripCandidates }
            ],
            "Výlet byla úspěšně načten",
            "Nepodařilo se načíst výlet",
            onTripLoaded
        )
    }
    return (
        <div className="relative w-full my-4">
            <CardGrid cardsPerRowCount={4}>
                {days?.map((day, index) => (
                    <DayCard
                        key={index}
                        day={day}
                        events={trip.getEvents(day, places, timezone)}
                        stay={trip.getStay(day)}
                        fitness={trip.fitness[index]}
                        publicHoliday={trip.getPublicHoliday(day)}
                        timezone={timezone}
                        onPhotosAdded={onPhotosAdded} />
                ))}
            </CardGrid>
            <div className="absolute bottom-3 right-3 flex items-center gap-2 z-50">
                {onTripMoved && isAdmin && !trip?.isDayTrips() && (
                    <button
                        onClick={handleMoved}
                        className="btn-chip-gray">
                        <ArrowRightLeft size={16} />
                    </button>
                )}
                {onTripLoaded && isAdmin && !trip?.isDayTrips() && (
                    <button
                        onClick={handleLoaded}
                        className="btn-chip-gray">
                        <Upload size={16} />
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
