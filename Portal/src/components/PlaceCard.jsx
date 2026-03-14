import { Link } from "react-router-dom"
import { useMemo } from "react"
import { Earth, Trash2 } from "lucide-react"
import LoadingCard from "./LoadingCard.tsx"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { getEntityPrettyName } from "../utils/formattingUtils.ts"
import { useFormatters } from "../hooks/useFormatters.ts"

export default function PlaceCard({ place, onPlaceRemoved }) {
    const { showRemovePlaceToast } = usePredefinedUserInput()
    const {formatKilometers} = useFormatters()

    const mostSpecificCategory = useMemo(() => place?.getCategory("country"), [place])

    const handleDelete = () => {
        showRemovePlaceToast(() => onPlaceRemoved(place.id))
    }

    return place ? (
        <div className="bg-white rounded-xl shadow-md max-w-xl mx-auto p-3 w-full space-y-1">
            <div className="flex justify-start items-center">
                {mostSpecificCategory && (
                    <img
                        src={`/img/flags/${mostSpecificCategory.metadata.unicode}.svg`}
                        alt={mostSpecificCategory.name}
                        className="w-7 h-auto flex-shrink-0" />
                )}
                <Link
                    to={`${window.location.pathname.startsWith("/plan") ? "/plan" : ""}/place/${place.id}`}
                    title={getEntityPrettyName(place.name)}
                    className="ml-2 hover:underline text-lg font-semibold truncate">
                    {getEntityPrettyName(place.name)}
                </Link>
                {onPlaceRemoved && (
                    <button
                        onClick={() => handleDelete(place)}
                        className="p-1 rounded text-red-800 hover:bg-gray-100 transition-colors ml-auto">
                        <Trash2 size={16} />
                    </button>
                )}
            </div>
            {place.distance && (
                <div className="text-sm text-gray-400">
                    {formatKilometers(place.distance)}
                </div>
            )}
            <ul>
                {place?.categories?.map(category => (
                    <li
                        key={category.id}
                        className="flex justify-start items-center space-x-2 my-2">
                        <span className="text-gray-600">
                            <Earth size={16} />
                        </span>
                        <Link
                            to={`${window.location.pathname.startsWith("/plan") ? "/plan" : ""}/category/${category.id}`}
                            className="text-gray-600 hover:underline hover:text-gray-300 transition-colors duration-200">
                            {getEntityPrettyName(category.name)}
                        </Link>
                    </li>
                ))}
            </ul>
        </div>
    ) : (
        <LoadingCard />
    )
}
