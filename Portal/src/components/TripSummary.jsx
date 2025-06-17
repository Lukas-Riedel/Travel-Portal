import { useMemo, useState } from "react"
import { eachDayOfInterval, fromUnixTime, startOfDay } from "date-fns"
import { getDateRangeString } from "../utils/helpers"
import DayCard from "./DayCard"
import { Link } from "react-router-dom"
import { useTrip } from "../hooks/useTrip"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { TailSpin } from "react-loader-spinner"
import { useConfiguration } from "../contexts/ConfigContext"
import { Earth, House } from "lucide-react"

export default function TripSummary({ tripId }) {
    const configuration = useConfiguration()

    const { trip } = useTrip(tripId)
    const tripPlaces = useRegularPlaces({ tripId, include: "CATEGORIES,DATES" })

    const [timezone, setTimezone] = useState(undefined)

    const days = useMemo(() => trip && eachDayOfInterval({
        start: startOfDay(fromUnixTime(Math.max(trip?.start, Date.now() / 1000))),
        end: startOfDay(fromUnixTime(trip?.end - 1))
    }), [trip])
    const tripPlacesWithoutLayover = useMemo(() => trip && tripPlaces?.filter(place => !place.dates?.some(date => date?.layover)), [tripPlaces])
    const countryCategories = useMemo(() => [...new Map(tripPlacesWithoutLayover?.map(place => place.getCategory("COUNTRY"))
        ?.filter(Boolean)?.map(category => [category.name, category])).values()].sort((a, b) => a.name.localeCompare(b.name)), [tripPlacesWithoutLayover])

    return (
        <div className="relative w-full grid grid-cols-[repeat(auto-fill,minmax(13rem,1fr))] items-center gap-4 bg-white p-3 my-3 text-sm">
            <div className="flex flex-col items-center justify-center text-center">
                {trip ? (
                    <>
                        {countryCategories && (
                            <div className="flex">
                                {countryCategories.map((category, index) => (
                                    <img
                                        key={index}
                                        className="w-7 object-cover mx-1.5 flex-shrink-0"
                                        src={`/img/flags/${category?.metadata?.unicode}.svg`}
                                        alt={category?.name} />
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
                    </>
                ) : (
                    <TailSpin
                        color="black"
                        height={30}
                        width={30} />
                )}
            </div>
            {days?.map((day, idx) => (
                <DayCard
                    key={idx}
                    day={day}
                    events={trip?.getEvents(day, tripPlaces, timezone)}
                    stay={trip?.getStay(day)}
                    fitness={trip?.fitness[idx]}
                    publicHoliday={trip?.getPublicHoliday(day)}
                    timezone={timezone}
                    onPhotosAdded={(placeId, albumId, timestamp, path, mainPhotoPosition) =>
                        api.createEvent("PhotosUploading", { placeId, albumId, timestamp, path, mainPhotoPosition })} />
            ))?.filter(Boolean)?.slice(0, 3)}
            <button
                onClick={() => setTimezone(prev => prev ? undefined : configuration?.homeLocation?.timezone)}
                className="absolute bottom-5 right-5 btn-chip-gray">
                {timezone ? <Earth size={16} /> : <House size={16} />}
            </button>
        </div>
    )
}