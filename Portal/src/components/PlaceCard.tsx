import { useMemo } from "react"
import { Earth, Trash2 } from "lucide-react"
import LoadingCard from "./LoadingCard.tsx"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { getEntityPrettyName } from "../utils/formattingUtils.ts"
import { useFormatters } from "../hooks/useFormatters.ts"
import type { Place } from "../classes/Place.ts"
import { InternalCategoryCategory } from "../types/InternalCategoryCategory.ts"
import type { DistanceAwarePlace } from "../classes/DistanceAwarePlace.ts"
import Card from "./Card.tsx"
import AppLink from "./AppLink.tsx"
import CategoryFlag from "./CategoryFlag.tsx"

interface PlaceCardProps {
    place: Place | DistanceAwarePlace | null
    onPlaceRemoved?: () => Promise<void>
}

const hasDistance = (place: Place | DistanceAwarePlace): place is DistanceAwarePlace => (place as DistanceAwarePlace).distance !== undefined

export default function PlaceCard({ place, onPlaceRemoved }: PlaceCardProps) {
    const { showRemovePlaceToast } = usePredefinedUserInput()
    const { formatKilometers } = useFormatters()

    const mostSpecificCategory = useMemo(() => place?.getCategory(InternalCategoryCategory.MostSpecificWithMetadata), [place])

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
                        className="p-1 rounded text-red-800 hover:bg-gray-100 transition-colors ml-auto">
                        <Trash2 size={16} />
                    </button>
                )}
            </div>
            {hasDistance(place) && (
                <div className="text-sm text-gray-400">
                    {formatKilometers(place.distance)}
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
                                className="text-gray-600 hover:underline hover:text-gray-300 transition-colors duration-200">
                                {getEntityPrettyName(category.name)}
                            </AppLink>
                        </li>
                    ))}
                </ul>
            )}
        </Card>
    )
}
