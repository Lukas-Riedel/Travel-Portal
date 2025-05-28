import { RefreshCcw } from "lucide-react"
import { useAuth } from "../contexts/AuthContext.jsx"
import showConfirmToast from "./ConfirmToast.jsx"
import PlaceMap from "./PlaceMap.jsx"

export default function PlaceContent({ place, onExcerptRefreshed }) {
    const { isAdmin } = useAuth()

    const handleExcerptRefresh = () => {
        showConfirmToast("Opravdu chceš znovu vygenerovat excerpt?",
            "Excerpt byl úspěšně aktualizován",
            "Nepodařilo se aktualizovat excerpt",
            onExcerptRefreshed
        )
    }

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <>
                <p className="text-gray-700 text-justify leading-relaxed relative">
                    {place.excerpt}
                    {onExcerptRefreshed && isAdmin() && (
                        <button
                            onClick={handleExcerptRefresh}
                            className="float-right ml-2 mb-1 rounded-full bg-white/80 backdrop-blur-sm text-black shadow-md hover:bg-gray-100 transition-colors px-3 py-1 text-sm font-medium inline-flex items-center space-x-2">
                            <RefreshCcw size={16} />
                        </button>
                    )}
                </p>
            </>
            <div className="w-full h-full overflow-hidden rounded-lg shadow">
                <PlaceMap places={[place]} />
            </div>
        </div >
    )
}