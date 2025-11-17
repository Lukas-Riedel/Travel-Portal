import { getSafeSvgString } from "../utils/helpers.js"
import { Link } from "react-router-dom"
import LoadingCard from "./LoadingCard.jsx"
import { Trash2, Wrench } from "lucide-react"
import showFormToast from "./FormToast.jsx"
import { useUserInput } from "../hooks/useUserInput.ts"

export default function AirlineCard({ airline, onAirlineNameUpdated, onAirlineLogoUpdated, onAirlineRemoved, onAirlineCodeRemoved }) {
    const { showConfirmToast } = useUserInput()

    const handleAirlineUpdated = airline => {
        showFormToast(
            "Zadej nové údaje o aerolince:",
            [
                { label: "Název", value: airline.name, required: true },
                { label: "Logo", value: airline.logo },
                { label: "Kódy", value: airline.codes, multiple: true, required: false, type: "select", options: airline.codes.map(code => ({ id: code, name: code })) }
            ],
            "Aerolinka byla úspěšně aktualizována",
            "Nepodařilo se aktualizovat aerolinku",
            async (name, logo, codes) => {
                if (airline.name !== name) {
                    await onAirlineNameUpdated(airline.id, name)
                }
                if (airline.logo !== logo) {
                    await onAirlineLogoUpdated(airline.id, logo)
                }
                await Promise.all(airline.codes.filter(code => !codes.includes(code)).map(code => onAirlineCodeRemoved(airline.id, code)))
            }
        )
    }

    const handleAirlineRemoved = airline => {
        showConfirmToast(
            `Opravdu chceš odstranit aerolinku ${airline.name}?`,
            "Aerolinka byla úspěšně odstraněna",
            "Nepodařilo se odstranit aerolinku",
            async () => onAirlineRemoved(airline.id)
        )
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
                        onClick={() => handleAirlineUpdated(airline)}
                        className="rounded text-orange-600 hover:bg-gray-100 transition-colors p-2">
                        <Wrench size={16} />
                    </button>
                )}
                {onAirlineRemoved && (
                    <button
                        onClick={() => handleAirlineRemoved(airline)}
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
