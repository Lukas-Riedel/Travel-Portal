import { MapPin, Move, Trash2 } from "lucide-react"
import { useCallback, useMemo } from "react"
import { formatKilometers, formatNextPlaces } from "../utils/formatters.js"
import LoadingCard from "./LoadingCard.tsx"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import type { Category } from "../types/CoreSwaggerTypes.ts"
import type { Place } from "../classes/Place.ts"
import type { DistanceAwarePlace } from "../classes/DistanceAwarePlace.ts"
import { getEntityPrettyName } from "../utils/formattingUtils.ts"
import Card from "./Card.tsx"
import AppLink from "./AppLink.tsx"
import { useTranslation } from "react-i18next"

const MAXIMUM_PLACES_COUNT = 5

interface CategoryCardProps {
    category: Category | null
    places: DistanceAwarePlace[] | Place[] | null
    onCurrentLocationChanged?: (place: Place) => void
    onMaximumDistanceChanged?: (distance: number) => void
    onPlaceRemoved?: (placeId: string) => Promise<void>
}

const hasDistance = (place: Place | DistanceAwarePlace): place is DistanceAwarePlace => (place as DistanceAwarePlace).distance !== undefined

export default function CategoryCard({ category, places, onCurrentLocationChanged, onMaximumDistanceChanged, onPlaceRemoved }: CategoryCardProps) {
    const { t } = useTranslation()
    const { showRemovePlaceToast } = usePredefinedUserInput()

    const visiblePlaces = useMemo(() => [...(places ?? [])].slice(0, MAXIMUM_PLACES_COUNT), [places])
    const remainingCount = useMemo(() => places?.length - visiblePlaces?.length, [places?.length, visiblePlaces?.length])

    const handlePlaceRemoved = useCallback((placeId: string) => {
        if (onPlaceRemoved) {
            showRemovePlaceToast(() => onPlaceRemoved(placeId))
        }
    }, [onPlaceRemoved])

    if (category && places && places.length === 0) {
        return null
    }

    if (!category || !places) {
        return (
            <LoadingCard />
        )
    }

    return (
        <Card>
            <div className="flex justify-start items-center space-x-2">
                <img
                    src={`/img/flags/${category.metadata.unicode}.svg`}
                    alt={category.name}
                    className="w-7 h-auto flex-shrink-0" />
                <AppLink
                    to={`/category/${category.id}`}
                    className="hover:underline text-lg font-semibold truncate">
                    {getEntityPrettyName(category.name)}
                </AppLink>
            </div>
            <ul className="mt-3">
                {visiblePlaces.map(place => (
                    <li
                        key={place.id}
                        className="my-2 space-y-1">
                        <div className="flex justify-start items-center">
                            {onCurrentLocationChanged ? (
                                <button
                                    className="text-indigo-600 hover:text-indigo-300 transition-colors duration-200"
                                    onClick={() => onCurrentLocationChanged(place)}>
                                    <MapPin size={16} />
                                </button>
                            ) : (
                                <span className="text-indigo-600">
                                    <MapPin size={16} />
                                </span>
                            )}
                            <AppLink
                                to={`/place/${place.id}`}
                                className="ml-2 text-indigo-600 hover:underline hover:text-indigo-300 transition-colors duration-200">
                                {getEntityPrettyName(place.name)}
                                {place?.quality > 0 ? ` (${Math.round(place.quality)} %)` : ""}
                            </AppLink>
                            {onPlaceRemoved && (
                                <button
                                    onClick={() => handlePlaceRemoved(place.id)}
                                    className="p-1 rounded text-red-800 hover:bg-gray-100 transition-colors ml-auto">
                                    <Trash2 size={16} />
                                </button>
                            )}
                        </div>
                        {hasDistance(place) && place.distance > 0 && (
                            <div className="flex justify-start items-center">
                                {onMaximumDistanceChanged ? (
                                    <button
                                        className="text-gray-600 hover:text-gray-300 transition-colors duration-200"
                                        onClick={() => onMaximumDistanceChanged(place.distance)}>
                                        <Move size={16} />
                                    </button>
                                ) : (
                                    <span className="text-gray-600">
                                        <Move size={16} />
                                    </span>
                                )}
                                <span className="ml-2 text-gray-600 text-xs">
                                    {formatKilometers(Math.round(place.distance))}
                                </span>
                            </div>
                        )}
                    </li>
                ))}
                {remainingCount > 0 && (
                    <li className="my-2">
                        <AppLink
                            to={`/category/${category.id}`}
                            className="text-gray-500 text-sm hover:underline">
                            {`${t("general.label.view")} ${formatNextPlaces(remainingCount)}`}
                        </AppLink>
                    </li>
                )}
            </ul>
        </Card>
    )
}