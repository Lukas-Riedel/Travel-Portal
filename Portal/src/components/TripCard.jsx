import { Link } from "react-router-dom"
import { useMemo } from "react"
import { Calendar, Trash2 } from "lucide-react"
import showConfirmToast from "./ConfirmToast"
import { useAuth } from "../contexts/AuthContext"
import { useCandidatePlaces } from "../hooks/useCandidatePlaces"
import { formatDays } from "../utils/formatters"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { getPrettyName } from "../utils/helpers"
import LoadingCard from "./LoadingCard"

export default function TripCard({ trip, onTripRemoved }) {
    const { isAdmin } = useAuth()

    const regularPlaces = useRegularPlaces({ tripId: trip?.id, include: "CATEGORIES,DATES" })
    const { candidatePlaces } = useCandidatePlaces({ tripId: trip?.id, include: "CATEGORIES,DATES" })
    const tripPlaces = useMemo(() => trip?.isCandidate() ? candidatePlaces : regularPlaces, [trip, regularPlaces, candidatePlaces])

    const tripPlacesWithoutLayover = useMemo(() => trip && tripPlaces?.filter(place => !place.dates?.some(date => date?.layover)), [tripPlaces])
    const countryCategories = useMemo(() => [...new Map(tripPlacesWithoutLayover?.map(place => place.getCategory("COUNTRY"))
        ?.filter(Boolean)?.map(category => [category.name, category])).values()].sort((a, b) => a.name.localeCompare(b.name)), [tripPlacesWithoutLayover])

    const days = useMemo(() => tripPlaces?.reduce((acc, place) => (
        place.dates.forEach(date => {
            const day = Math.floor(date.start / (24 * 60 * 60))
                ; (acc[day] ??= []).push({ ...place, date })
        }), acc), {}), [tripPlaces])

    const handleDelete = () => {
        showConfirmToast(
            "Opravdu chceš odstranit výlet '" + trip.name + "'?",
            "Výlet byl úspěšně odstraněn",
            "Nepodařilo se odstranit výlet",
            async () => onTripRemoved(trip.id)
        )
    }

    return trip && tripPlaces ? (
        <div className="bg-white rounded-xl shadow-md max-w-xl mx-auto p-3 w-full space-y-1">
            <div className="flex justify-start items-center">
                {countryCategories && (
                    <div className="flex">
                        {countryCategories.map(category => (
                            <img
                                key={category.id}
                                className="w-7 object-cover mx-1 flex-shrink-0"
                                src={`/img/flags/${category.metadata?.unicode}.svg`}
                                alt={category.name} />
                        ))}
                    </div>)}
                <Link
                    to={`/plan/trip/${trip.id}`}
                    title={trip.name}
                    className="ml-2 hover:underline text-lg font-semibold truncate">
                    {trip.name}
                </Link>
                {isAdmin && onTripRemoved && (
                    <button
                        onClick={() => handleDelete(trip)}
                        className="p-1 rounded text-red-800 hover:bg-gray-100 transition-colors ml-auto">
                        <Trash2 size={16} />
                    </button>
                )}
            </div>
            {tripPlaces && (
                <div className="text-sm text-gray-400">
                    {formatDays(Math.floor(Math.max(...tripPlaces.flatMap(place => place.dates)?.map(date => date.end)) / 86400) + 1)}
                </div>
            )}
            <ul>
                {Object.keys(days ?? []).map(day => (
                    <li
                        key={day}
                        className="my-2 space-y-1">
                        <div className="flex justify-start items-center">
                            <span className="text-indigo-600">
                                <Calendar size={16} />
                            </span>
                            <span className="ml-2 text-indigo-600 truncate">
                                {days[day].flatMap((place, index) => [
                                    index > 0 && <span key={`sep-${index}`}>, </span>,
                                    <Link
                                        key={index}
                                        to={`/plan/place/${place.id}`}
                                        title={getPrettyName(place.name)}
                                        className="hover:underline hover:text-indigo-300 transition-colors duration-200">
                                        {getPrettyName(place.name)}
                                    </Link>
                                ])}
                            </span>
                        </div>
                    </li>
                ))}

            </ul>
        </div>
    ) : (
        <LoadingCard />
    )
}
