import { Calendar, Trash2 } from "lucide-react"

import { useCandidatePlaces } from "../hooks/useCandidatePlaces.js"
import { useFormatters } from "../hooks/useFormatters.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { useRegularPlaces } from "../hooks/useRegularPlaces.js"
import { CategoryCategory, type Date, type Place, PlaceIncludedEntity, type Trip } from "../types/CoreSwaggerTypes.ts"
import { getEntityPrettyName } from "../utils/formattingUtils.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import AppLink from "./AppLink.tsx"
import Card from "./Card.tsx"
import CategoryFlag from "./CategoryFlag.tsx"
import LoadingCard from "./LoadingCard.tsx"

interface TripCardProps {
    trip: Trip | null
    onTripRemoved?: (tripId: string) => Promise<void>
}

export default function TripCard({ trip, onTripRemoved }: TripCardProps) {
    const { showRemoveTripToast } = usePredefinedUserInput()
    const { formatDays } = useFormatters()

    const { places } = useRegularPlaces({ tripId: trip?.id, include: [PlaceIncludedEntity.Categories, PlaceIncludedEntity.Dates] })
    const { candidatePlaces } = useCandidatePlaces({ tripId: trip?.id, include: [PlaceIncludedEntity.Categories, PlaceIncludedEntity.Dates] })

    const tripPlaces = trip && (places?.length ? places : candidatePlaces)
    const tripPlacesWithoutLayover = trip && tripPlaces?.filter(place => !place.dates?.some((date => date?.layover)))

    const countryCategories = (() => {
        const categoryMap = new Map()
        tripPlacesWithoutLayover?.forEach(place => {
            const category = place.getCategory(CategoryCategory.Country)
            if (category) {
                categoryMap.set(category.name, category)
            }
        })

        return Array.from(categoryMap.values()).sort((a, b) => a.name.localeCompare(b.name))
    })()

    const flatPlaces = tripPlaces?.flatMap(place => (place.dates ?? []).map(date => ({ ...place, ...date }))) ?? []
    const days: Partial<Record<number, (Place & Date)[]>> = Object.groupBy(flatPlaces, ({ start }) => Math.floor(start / (ONE_DAY_SECONDS)))

    const totalDays = tripPlaces?.length ? (Math.floor(Math.max(...tripPlaces.flatMap(place => place.dates ?? []).map(date => date.end)) / ONE_DAY_SECONDS) + 1) : 0

    const handleTripRemoved = () => {
        if (trip?.id && onTripRemoved) {
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
                        onClick={handleTripRemoved}
                        className="btn-ghost-danger ml-auto">
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
                                        className="link-hover hover:text-indigo-300">
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