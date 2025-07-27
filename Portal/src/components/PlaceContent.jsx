import { ImagePlus, LocationEdit, RefreshCcw, SquarePen } from "lucide-react"
import { useAuth } from "../contexts/AuthContext.jsx"
import showConfirmToast from "./ConfirmToast.jsx"
import PlaceMap from "./PlaceMap.jsx"
import showInputToast from "./InputToast.jsx"
import { TailSpin } from "react-loader-spinner"
import showFormToast from "./FormToast.jsx"
import { getTime, parseISO } from "date-fns"

export default function PlaceContent({ place, onPhotosAdded, onExcerptChanged, onAddressChanged, onExcerptRefreshed, onLocationChanged }) {
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
            "Nahrávání fotek bude brzy zahájeno",
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

    const handleAddressChanged = () => {
        showInputToast("Zadej novou adresu:",
            place.name,
            "Adresa byla úspěšně aktualizována",
            "Nepodařilo se aktualizovat adresu",
            onAddressChanged
        )
    }

    return place ? (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <div className="relative mx-2 sm:mx-0 text-gray-700 leading-relaxed">
                <p className="text-justify">
                    {place.excerpt}
                </p>
                {isAdmin && (
                    <div className="flex justify-end space-x-2 mt-2">
                        {onAddressChanged && (
                            <button
                                onClick={handleAddressChanged}
                                className="btn-chip-gray">
                                <LocationEdit size={16} />
                            </button>
                        )}
                        {onExcerptRefreshed && (
                            <button
                                onClick={handleExcerptRefreshed}
                                className="btn-chip-gray-inline">
                                <RefreshCcw size={16} />
                            </button>
                        )}
                        {onExcerptChanged && (
                            <button
                                onClick={handleExcerptChanged}
                                className="btn-chip-gray-inline">
                                <SquarePen size={16} />
                            </button>
                        )}
                        {onPhotosAdded && (
                            <button
                                onClick={handlePhotosAdded}
                                className="btn-chip-gray-inline">
                                <ImagePlus size={16} />
                            </button>
                        )}
                    </div>
                )}
            </div>

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