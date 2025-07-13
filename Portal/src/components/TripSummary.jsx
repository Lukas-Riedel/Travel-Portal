import { useEffect, useMemo, useState } from "react"
import { eachDayOfInterval, fromUnixTime, startOfDay } from "date-fns"
import { getDateRangeString } from "../utils/helpers"
import DayCard from "./DayCard"
import { Link } from "react-router-dom"
import { useTrip } from "../hooks/useTrip"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { TailSpin } from "react-loader-spinner"
import { useConfiguration } from "../contexts/ConfigContext"
import { Earth, House } from "lucide-react"
import { useEvents } from "../hooks/useEvents"

export default function TripSummary({ tripId }) {
    const configuration = useConfiguration()
    const { publishPhotosUploadingTriggeredEvent } = useEvents()

    const { trip } = useTrip(tripId)
    const tripPlaces = useRegularPlaces({ tripId, include: "CATEGORIES,DATES" })

    const [timezone, setTimezone] = useState(undefined)
    useEffect(() => {
        setTimezone(configuration?.homeLocation?.timezone)
    }, [configuration])

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
        start: startOfDay(fromUnixTime(Math.max(trip?.start, Date.now() / 1000))),
        end: startOfDay(fromUnixTime(trip?.end - 1))
    }), [trip])
    const tripPlacesWithoutLayover = useMemo(() => trip && tripPlaces?.filter(place => !place.dates?.some(date => date?.layover)), [tripPlaces])
    const countryCategories = useMemo(() => [...new Map(tripPlacesWithoutLayover?.map(place => place.getCategory("COUNTRY"))
        ?.filter(Boolean)?.map(category => [category.name, category])).values()].sort((a, b) => a.name.localeCompare(b.name)), [tripPlacesWithoutLayover])

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
            </div>
            {days?.map((day, index) => (
                <DayCard
                    key={index}
                    day={day}
                    events={trip?.getEvents(day, tripPlaces, timezone)}
                    stay={trip?.getStay(day)}
                    fitness={trip?.fitness[(day - startOfTripStartDay) / (86400 * 1000)]}
                    publicHoliday={trip?.getPublicHoliday(day)}
                    timezone={timezone}
                    onPhotosAdded={publishPhotosUploadingTriggeredEvent} />
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