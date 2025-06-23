import { RefreshCcw, SquarePen } from "lucide-react"
import { useAuth } from "../contexts/AuthContext.jsx"
import showConfirmToast from "./ConfirmToast.jsx"
import PlaceMap from "./PlaceMap.jsx"
import showInputToast from "./InputToast.jsx"
import { TailSpin } from "react-loader-spinner"

export default function PlaceContent({ place, onExcerptChanged, onExcerptRefreshed, onLocationChanged }) {
    const { isAdmin } = useAuth()

    const handleExcerptChanged = () => {
        showInputToast("Zadej nový excerpt:",
            place.excerpt,
            "Excerpt byl úspěšně aktualizován",
            "Nepodařilo se aktualizovat excerpt",
            onExcerptChanged
        )
    }

    const handleExcerptRefreshed = () => {
        showConfirmToast("Opravdu chceš znovu vygenerovat excerpt?",
            "Excerpt byl úspěšně aktualizován",
            "Nepodařilo se aktualizovat excerpt",
            onExcerptRefreshed
        )
    }

    const handleLocationUpdated = (latitude, longitude) => {
        if (!isAdmin) {
            return
        }

        showConfirmToast("Opravdu chceš změnit polohu místa na zvolené souřadnice?",
            "Poloha místa byla úspěšně aktualizována",
            "Nepodařilo se aktualizovat polohu místa",
            async () => onLocationChanged(latitude, longitude))
    }

    return place ? (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <p className="text-gray-700 text-justify leading-relaxed relative mx-2 sm:mx-0">
                {place.excerpt}
                {onExcerptRefreshed && isAdmin && (
                    <button
                        onClick={handleExcerptRefreshed}
                        className="float-right ml-2 mb-1 btn-chip-gray-inline">
                        <RefreshCcw size={16} />
                    </button>
                )}
                {onExcerptChanged && isAdmin && (
                    <button
                        onClick={handleExcerptChanged}
                        className="float-right ml-2 mb-1 btn-chip-gray-inline">
                        <SquarePen size={16} />
                    </button>
                )}
            </p>
            <PlaceMap
                places={[place]}
                placeMainCategorySelector={place => place?.getCategory("MOST_SPECIFIC_WITH_METADATA")}
                onRightClick={handleLocationUpdated} />
        </div>
    ) : (
        <div className="flex justify-center items-center min-h-[400px]">
            <TailSpin
                color="black"
                height={80}
                width={80} />
        </div>
    )
}