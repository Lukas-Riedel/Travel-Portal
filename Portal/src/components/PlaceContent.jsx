import { ImagePlus, RefreshCcw, SquarePen } from "lucide-react"
import { useAuth } from "../contexts/AuthContext.jsx"
import showConfirmToast from "./ConfirmToast.jsx"
import PlaceMap from "./PlaceMap.jsx"
import showInputToast from "./InputToast.jsx"
import { TailSpin } from "react-loader-spinner"
import showFormToast from "./FormToast.jsx"
import { getTime, parseISO } from "date-fns"

export default function PlaceContent({ place, onPhotosAdded, onExcerptChanged, onExcerptRefreshed, onLocationChanged }) {
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

    const handlePhotosAdded = () => {
        showFormToast(
            "Zadej datum a cestu k fotkám k nahrání:",
            [
                { label: "Datum", required: true, type: "date" },
                { label: "Cesta", required: true },
                { label: "Pozice hlavní fotky", required: false, type: "number", min: 1 }
            ],
            "Nahrávání fotek brzy začne",
            "Při nahrávání fotek došlo k chybě",
            async (date, path, mainPhotoPosition) => {
                const placeDate = place.getDate(parseISO(date))
                const timestamp = Math.floor(getTime(parseISO(date)) / 1000)
                if (!place.isPermanent() && !placeDate) {
                    return Promise.reject("Unable to upload photos for the regular place for the date that does not exist.")
                }
                return onPhotosAdded(place.id, placeDate?.album?.id, timestamp, path, mainPhotoPosition)
            }
        )
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
                {onPhotosAdded && isAdmin && (
                    <button
                        onClick={handlePhotosAdded}
                        className="float-right ml-2 mb-1 btn-chip-gray-inline">
                        <ImagePlus size={16} />
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