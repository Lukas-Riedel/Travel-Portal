import { getSafeSvgString } from "../utils/helpers.js"
import { Link } from "react-router-dom"
import LoadingCard from "./LoadingCard.jsx"
import { Trash2, Wrench } from "lucide-react"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"

export default function AirlineCard({ airline, onAirlineNameUpdated, onAirlineLogoUpdated, onAirlineRemoved, onAirlineCodeRemoved }) {
    const { showUpdateAirlineToast, showRemoveAirlineToast } = usePredefinedUserInput()

    const handleAirlineUpdated = () => {
        showUpdateAirlineToast(airline,
            // TODO: No need to propagate airline.id here, do it in the caller.
            name => onAirlineNameUpdated(airline.id, name),
            logo => onAirlineLogoUpdated(airline.id, logo),
            code => onAirlineCodeRemoved(airline.id, code))
    }

    const handleAirlineRemoved = () => {
        showRemoveAirlineToast(() => onAirlineRemoved(airline.id))
    }

    return airline ? (
        <div className="bg-white rounded-xl shadow-md p-3 w-full flex flex-col h-full text-center">
            <div className="flex flex-col items-center justify-center flex-grow space-y-4">
                <div className="flex-shrink-0 w-20 h-20 flex items-center justify-center">
                    {airline.logo ? (
                        <div
                            className="max-w-full max-h-full"
                            style={{
                                width: "100%",
                                height: "100%",
                                display: "flex",
                                alignItems: "center",
                                justifyContent: "center",
                            }}
                            dangerouslySetInnerHTML={{ __html: getSafeSvgString(airline.logo, airline.codes.join()) }} />
                    ) : (
                        <div className="text-gray-400 text-sm">
                            Logo není k dispozici
                        </div>
                    )}
                </div>
                <div className="flex flex-col items-center space-y-1">
                    <Link
                        to={`/airline/${airline.id}`}
                        className="font-semibold text-lg hover:underline">
                        {airline.name}
                    </Link>
                    <div className="text-gray-800">
                        {airline.codes.join(", ")}
                    </div>
                </div>
            </div>
            <div className="mt-4">
                {onAirlineNameUpdated && onAirlineLogoUpdated && (
                    <button
                        onClick={handleAirlineUpdated}
                        className="rounded text-orange-600 hover:bg-gray-100 transition-colors p-2">
                        <Wrench size={16} />
                    </button>
                )}
                {onAirlineRemoved && (
                    <button
                        onClick={handleAirlineRemoved}
                        className="rounded text-red-600 hover:bg-gray-100 transition-colors p-2">
                        <Trash2 size={16} />
                    </button>
                )}
            </div>
        </div>
    ) : (
        <LoadingCard />
    )
}
