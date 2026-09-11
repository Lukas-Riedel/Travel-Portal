import { Earth, Trash2 } from "lucide-react"

import type { DistanceAwarePlace } from "../types/DistanceAwarePlace.ts"
import type { Place } from "../types/CoreSwaggerTypes.ts"
import { useFormatters } from "../hooks/useFormatters.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { InternalCategoryCategory } from "../types/InternalCategoryCategory.ts"
import { getEntityPrettyName } from "../utils/formattingUtils.ts"
import { getPlaceCategory } from "../utils/placeUtils.ts"
import AppLink from "./AppLink.tsx"
import Card from "./Card.tsx"
import CategoryFlag from "./CategoryFlag.tsx"
import LoadingCard from "./LoadingCard.tsx"

interface PlaceCardProps {
    place: Place | DistanceAwarePlace | null
    onPlaceRemoved?: () => Promise<void>
}

const hasDistance = (place: Place | DistanceAwarePlace): place is DistanceAwarePlace => (place as DistanceAwarePlace).distance !== undefined

export default function PlaceCard({ place, onPlaceRemoved }: PlaceCardProps) {
    const { showRemovePlaceToast } = usePredefinedUserInput()
    const { formatKilometers } = useFormatters()

    const mostSpecificCategory = place && getPlaceCategory(place, InternalCategoryCategory.MostSpecificWithMetadata)

    const handlePlaceRemoved = () => {
        if (onPlaceRemoved) {
            showRemovePlaceToast(onPlaceRemoved)
        }
    }

    if (!place) {
        return (
            <LoadingCard />
        )
    }

    return (
        <Card>
            <div className="flex justify-start items-center">
                {mostSpecificCategory && (
                    <CategoryFlag
                        category={mostSpecificCategory}
                        className="w-7 h-auto flex-shrink-0" />
                )}
                <AppLink
                    to={place}
                    className="ml-2 hover:underline text-lg font-semibold truncate">
                    {getEntityPrettyName(place.name)}
                </AppLink>
                {onPlaceRemoved && (
                    <button
                        onClick={handlePlaceRemoved}
                        className="btn-ghost-danger ml-auto">
                        <Trash2 size={16} />
                    </button>
                )}
            </div>
            {hasDistance(place) && (
                <div className="text-sm text-gray-400">
                    {formatKilometers(place.distance!)}
                </div>
            )}
            {place.categories?.length && (
                <ul className="mt-3">
                    {place.categories?.map(category => (
                        <li
                            key={category.id}
                            className="flex justify-start items-center space-x-2 my-2">
                            <span className="text-gray-600">
                                <Earth size={16} />
                            </span>
                            <AppLink
                                to={category}
                                className="text-gray-600 link-hover hover:text-gray-300">
                                {getEntityPrettyName(category.name)}
                            </AppLink>
                        </li>
                    ))}
                </ul>
            )}
        </Card>
    )
}
