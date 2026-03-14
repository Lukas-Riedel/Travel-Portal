import { Link } from "react-router-dom"
import LoadingCard from "./LoadingCard.tsx"
import { Trash2, Wrench } from "lucide-react"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import type { Airline } from "../types/CoreSwaggerTypes.ts"
import Card from "./Card.tsx"
import { useTranslation } from "react-i18next"
import { getSafeSvgString } from "../utils/imageUtils.ts"
import { useCallback } from "react"
import AppLink from "./AppLink.tsx"

interface AirlineCardProps {
    airline: Airline | null
    onAirlineNameUpdated?: (name: string) => Promise<Airline>
    onAirlineLogoUpdated?: (logo: string) => Promise<Airline>
    onAirlineRemoved?: () => Promise<void>
    onAirlineCodeRemoved?: (code: string) => Promise<void>
}

export default function AirlineCard({ airline, onAirlineNameUpdated, onAirlineLogoUpdated, onAirlineRemoved, onAirlineCodeRemoved }: AirlineCardProps) {
    const { t } = useTranslation()
    const { showUpdateAirlineToast, showRemoveAirlineToast } = usePredefinedUserInput()

    const handleAirlineUpdated = useCallback(() => {
        if (airline && onAirlineNameUpdated && onAirlineLogoUpdated && onAirlineCodeRemoved) {
            showUpdateAirlineToast(airline,
                onAirlineNameUpdated,
                onAirlineLogoUpdated,
                onAirlineCodeRemoved
            )
        }
    }, [airline, onAirlineNameUpdated, onAirlineLogoUpdated, onAirlineCodeRemoved, showUpdateAirlineToast])

    const handleAirlineRemoved = useCallback(() => {
        if (onAirlineRemoved) {
            showRemoveAirlineToast(onAirlineRemoved)
        }
    }, [onAirlineRemoved, showRemoveAirlineToast])

    if (!airline) {
        return (
            <LoadingCard />
        )
    }

    return (
        <Card className="text-center flex flex-col items-center justify-center">
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
                            {t("general.placeholder.logo")}
                        </div>
                    )}
                </div>
                <div className="flex flex-col items-center space-y-1">
                    <AppLink
                        to={`/airline/${airline.id}`}
                        className="font-semibold text-lg hover:underline">
                        {airline.name}
                    </AppLink>
                    <div className="text-gray-800">
                        {airline.codes.join(", ")}
                    </div>
                </div>
            </div>
            <div className="mt-4">
                {onAirlineNameUpdated && onAirlineLogoUpdated && onAirlineCodeRemoved && (
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
        </Card>
    )
}
