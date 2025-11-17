import { Link } from "react-router-dom"
import { getPrettyName } from "../utils/helpers"
import { useMemo } from "react"
import { Earth, Trash2 } from "lucide-react"
import { useUserInput } from "../hooks/useUserInput.ts"
import { useAuth } from "../contexts/AuthContext"
import { formatKilometers } from "../utils/formatters"
import LoadingCard from "./LoadingCard"

export default function PlaceCard({ place, onPlaceRemoved }) {
    const { isAdmin } = useAuth()
    const { showConfirmToast } = useUserInput()

    const mostSpecificCategory = useMemo(() => place?.getCategory("country"), [place])

    const handleDelete = () => {
        showConfirmToast(
            "Opravdu chceš odstranit místo '" + place.name + "'?",
            async () => onPlaceRemoved(place.id),
            "Místo bylo úspěšně odstraněno",
            "Nepodařilo se odstranit místo"
        )
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
                    title={getPrettyName(place.name)}
                    className="ml-2 hover:underline text-lg font-semibold truncate">
                    {getPrettyName(place.name)}
                </Link>
                {isAdmin && onPlaceRemoved && (
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
                            {getPrettyName(category.name)}
                        </Link>
                    </li>
                ))}
            </ul>
        </div>
    ) : (
        <LoadingCard />
    )
}
