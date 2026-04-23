import { useMemo } from "react"
import { Link } from "react-router-dom"
import { Calendar, Trash2 } from "lucide-react"
import LoadingCard from "./LoadingCard.tsx"
import { useCandidatePlaces } from "../hooks/useCandidatePlaces.js"
import { useRegularPlaces } from "../hooks/useRegularPlaces.js"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { getEntityPrettyName } from "../utils/formattingUtils.ts"
import { useFormatters } from "../hooks/useFormatters.ts"
import { CategoryCategory, PlaceIncludedEntity, type Date, type Place, type Trip } from "../types/CoreSwaggerTypes.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import Card from "./Card.tsx"
import CategoryFlag from "./CategoryFlag.tsx"
import AppLink from "./AppLink.tsx"

interface TripCardProps {
    trip: Trip | null
    onTripRemoved?: (tripId: string) => Promise<void>
}

export default function TripCard({ trip, onTripRemoved }: TripCardProps) {
    const { showRemoveTripToast } = usePredefinedUserInput()
    const { formatDays } = useFormatters()

    const { places } = useRegularPlaces({ tripId: trip?.id, include: [PlaceIncludedEntity.Categories, PlaceIncludedEntity.Dates] })
    const { candidatePlaces } = useCandidatePlaces({ tripId: trip?.id, include: [PlaceIncludedEntity.Categories, PlaceIncludedEntity.Dates] })

    const tripPlaces = useMemo(() => trip && (places?.length ? places : candidatePlaces), [trip, places, candidatePlaces])
    const tripPlacesWithoutLayover = useMemo(() => trip && tripPlaces?.filter(place => !place.dates?.some((date => date?.layover))), [trip, tripPlaces])

    const countryCategories = useMemo(() => {
        const categoryMap = new Map()
        tripPlacesWithoutLayover?.forEach(place => {
            const category = place.getCategory(CategoryCategory.Country)
            if (category) {
                categoryMap.set(category.name, category)
            }
        })

        return Array.from(categoryMap.values()).sort((a, b) => a.name.localeCompare(b.name))
    }, [tripPlacesWithoutLayover])

    const days = useMemo<Record<number, (Place & Date)[]>>(() => {
        const flatPlaces = tripPlaces?.flatMap(place => place.dates.map(date => ({ ...place, ...date }))) ?? []
        return Object.groupBy(flatPlaces, ({ start }) => Math.floor(start / (ONE_DAY_SECONDS)))
    }, [tripPlaces])

    const totalDays = useMemo(() => {
        if (!tripPlaces || tripPlaces.length === 0) {
            return 0
        }

        const maxEnd = Math.max(...tripPlaces.flatMap(place => place.dates).map(date => date.end))
        return Math.floor(maxEnd / ONE_DAY_SECONDS) + 1
    }, [tripPlaces])

    const handleDelete = () => {
        if (onTripRemoved) {
            showRemoveTripToast(() => onTripRemoved(trip.id))
        }
    }

    if (!trip || !tripPlaces) {
        return (
            <LoadingCard />
        )
    }

    return trip && tripPlaces && (
        <Card>
            <div className="flex justify-start items-center">
                {countryCategories && countryCategories.length > 0 && (
                    <div className="flex">
                        {countryCategories.map(category => (
                            <CategoryFlag
                                key={category.id}
                                category={category}
                                className="w-7 object-cover mx-1 flex-shrink-0" />
                        ))}
                    </div>
                )}
                <AppLink
                    to={trip}
                    className="ml-2 hover:underline text-lg font-semibold truncate">
                    {trip.name}
                </AppLink>
                {onTripRemoved && (
                    <button
                        onClick={handleDelete}
                        className="p-1 rounded text-red-800 hover:bg-gray-100 transition-colors ml-auto">
                        <Trash2 size={16} />
                    </button>
                )}
            </div>
            {tripPlaces && totalDays > 0 && (
                <div className="text-sm text-gray-400">
                    {formatDays(totalDays)}
                </div>
            )}
            <ul>
                {Object.entries(days).map(([day, places]) => (
                    <li
                        key={day}
                        className="my-2 space-y-1">
                        <div className="flex justify-start items-center">
                            <span className="text-indigo-600">
                                <Calendar size={16} />
                            </span>
                            <span className="ml-2 text-indigo-600 truncate">
                                {places?.flatMap((place, index) => [
                                    index > 0 && <span key={`sep-${index}`}>, </span>,
                                    <AppLink
                                        key={index}
                                        to={place}
                                        className="hover:underline hover:text-indigo-300 transition-colors duration-200">
                                        {getEntityPrettyName(place.name)}
                                    </AppLink>
                                ])}
                            </span>
                        </div>
                    </li>
                ))}
            </ul>
        </Card>
    )
}