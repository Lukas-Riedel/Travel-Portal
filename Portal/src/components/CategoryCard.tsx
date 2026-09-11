import { MapPin, Move, Trash2 } from "lucide-react"
import { useTranslation } from "react-i18next"

import type { DistanceAwarePlace } from "../types/DistanceAwarePlace.ts"
import type { Place } from "../types/CoreSwaggerTypes.ts"
import { useFormatters } from "../hooks/useFormatters.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { type Category,CategoryCategory } from "../types/CoreSwaggerTypes.ts"
import { getOnlyElement } from "../utils/collectionUtils.ts"
import { getEntityPrettyName } from "../utils/formattingUtils.ts"
import { getPlaceCategory } from "../utils/placeUtils.ts"
import AppLink from "./AppLink.tsx"
import Card from "./Card.tsx"
import CategoryFlag from "./CategoryFlag.tsx"
import LoadingCard from "./LoadingCard.tsx"

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
    const { formatKilometers } = useFormatters()

    const visiblePlaces = [...(places ?? [])].slice(0, MAXIMUM_PLACES_COUNT)
    const remainingCount = (places?.length ?? 0) - visiblePlaces.length

    const handlePlaceRemoved = (placeId: string) => {
        if (onPlaceRemoved) {
            showRemovePlaceToast(() => onPlaceRemoved(placeId))
        }
    }

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
                <CategoryFlag
                    category={category.metadata?.unicode ? category : (getOnlyElement(places.map((place: Place) => getPlaceCategory(place, CategoryCategory.Country)).filter((category, index, self) => category && self.findIndex(other => other?.id === category.id) === index)) ?? null)}
                    className="w-7 h-auto flex-shrink-0" />
                <AppLink
                    to={category}
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
                                to={place}
                                className="ml-2 text-indigo-600 link-hover hover:text-indigo-300">
                                {getEntityPrettyName(place.name)}
                                {(place?.quality ?? 0) > 0 ? ` (${Math.round(place.quality ?? 0)} %)` : ""}
                            </AppLink>
                            {onPlaceRemoved && (
                                <button
                                    onClick={() => handlePlaceRemoved(place.id)}
                                    className="btn-ghost-danger ml-auto">
                                    <Trash2 size={16} />
                                </button>
                            )}
                        </div>
                        {hasDistance(place) && (place.distance ?? 0) > 0 && (
                            <div className="flex justify-start items-center">
                                {onMaximumDistanceChanged ? (
                                    <button
                                        className="text-gray-600 hover:text-gray-300 transition-colors duration-200"
                                        onClick={() => onMaximumDistanceChanged(place.distance ?? 0)}>
                                        <Move size={16} />
                                    </button>
                                ) : (
                                    <span className="text-gray-600">
                                        <Move size={16} />
                                    </span>
                                )}
                                <span className="ml-2 text-gray-600 text-xs">
                                    {formatKilometers(Math.round(place.distance ?? 0))}
                                </span>
                            </div>
                        )}
                    </li>
                ))}
                {remainingCount > 0 && (
                    <li className="my-2">
                        <AppLink
                            to={category}
                            className="text-gray-500 text-sm hover:underline">
                            {t("place.show.more", { count: remainingCount })}
                        </AppLink>
                    </li>
                )}
            </ul>
        </Card>
    )
}