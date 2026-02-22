import { Link } from "react-router-dom"
import { getPrettyName } from "../utils/helpers"
import { MapPin, Move, Trash2 } from "lucide-react"
import { useMemo } from "react"
import { formatKilometers, formatNextPlaces } from "../utils/formatters"
import LoadingCard from "./LoadingCard"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"

const maximumPlacesCount = 5

export default function CategoryCard({ category, places, onCurrentLocationChanged, onMaximumDistanceChanged, onPlaceRemoved }) {
    const { showRemovePlaceToast } = usePredefinedUserInput()

    const visiblePlaces = useMemo(() => [...(places ?? [])]?.sort((a, b) => a.distance - b.distance)?.slice(0, maximumPlacesCount), [places])
    const remainingCount = useMemo(() => places?.length - visiblePlaces?.length, [places, visiblePlaces])

    const handleDelete = place => {
        showRemovePlaceToast(() => onPlaceRemoved(place.id))
    }

    if (category && places && places.length === 0) {
        return null
    }

    return category && places ? (
        <div className="bg-white rounded-xl shadow-md max-w-xl mx-auto p-3 w-full">
            <div className="flex justify-start items-center space-x-2">
                <img
                    src={`/img/flags/${category.metadata.unicode}.svg`}
                    alt={category.name}
                    className="w-7 h-auto flex-shrink-0" />
                <Link
                    to={`${window.location.pathname.startsWith("/plan") ? "/plan" : ""}/category/${category.id}`}
                    className="hover:underline text-lg font-semibold truncate">
                    {getPrettyName(category.name)}
                </Link>
            </div>
            <ul>
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
                            <Link
                                to={`${window.location.pathname.startsWith("/plan") ? "/plan" : ""}/place/${place.id}`}
                                className="ml-2 text-indigo-600 hover:underline hover:text-indigo-300 transition-colors duration-200">
                                {getPrettyName(place.name)}
                                {place.quality && place.quality >= 0 && ` (${Math.round(place.quality)} %)`}
                            </Link>
                            {onPlaceRemoved && (
                                <button
                                    onClick={() => handleDelete(place)}
                                    className="p-1 rounded text-red-800 hover:bg-gray-100 transition-colors ml-auto">
                                    <Trash2 size={16} />
                                </button>
                            )}
                        </div>
                        {place.distance > 0 && (
                            <div className="flex justify-start items-center">
                                {onMaximumDistanceChanged ? (
                                    <button
                                        className="text-gray-600 hover:text-gray-300 transition-colors duration-200"
                                        onClick={() => onMaximumDistanceChanged && onMaximumDistanceChanged(place.distance)}>
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
                        <Link
                            to={`${window.location.pathname.startsWith("/plan") ? "/plan" : ""}/category/${category.id}`}
                            className="text-gray-500 text-sm hover:underline">
                            Zobrazit {formatNextPlaces(remainingCount)}
                        </Link>
                    </li>
                )}
            </ul>
        </div>
    ) : (
        <LoadingCard />
    )
}