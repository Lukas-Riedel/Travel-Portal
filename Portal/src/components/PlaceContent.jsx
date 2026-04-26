import { ImagePlus, LocationEdit, RefreshCcw, SquarePen, TriangleAlert } from "lucide-react"
import { useAuth } from "../contexts/AuthContext.jsx"
import { useUserInput } from "../hooks/useUserInput.tsx"
import PlaceMap from "./PlaceMap.jsx"
import { TailSpin } from "react-loader-spinner"
import { getTime, parseISO } from "date-fns"
import { useDevices } from "../hooks/useDevices.js"
import { useEffect, useState } from "react"
import { UserRole } from "../types/CoreSwaggerTypes.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"

const agentOnlineStatusThresholdSeconds = 60

const checklistItems = [
    "Zkontrolovat polohu",
    "Zkontrolovat kategorie",
    "Zkontrolovat výňatek",
    "Nastavit highlighty",
    "Nastavit hlavní highlight",
    "Nastavit atributy highlightů",
    "Nastavit úvodní fotky alb",
    "Nastavit štítky"
]

export default function PlaceContent({ place, onPhotosAdded, onExcerptChanged, onAddressChanged, onExcerptRefreshed, onLocationChanged, onPlaceReviewed }) {
    const { hasRole } = useAuth()

    const agents = useDevices({ type: "agent" })
    const { showUploadPhotosToast, showRefreshPlaceExcerptToast, showUpdatePlaceReviewedToast, showUpdatePlaceLocationToast, showUpdatePlaceExcerptToast, showUpdatePlaceAddressToast } = usePredefinedUserInput()

    const [checked, setChecked] = useState({})

    const handleChange = key => {
        setChecked(prev => ({ ...prev, [key]: !prev[key] }))
    }

    const progress = (Object.values(checked).filter(Boolean).length / checklistItems.length) * 100

    const handleExcerptChanged = () => {
        showUpdatePlaceExcerptToast(place, onExcerptChanged)
    }

    const handleExcerptRefreshed = () => {
        showRefreshPlaceExcerptToast(onExcerptRefreshed)
    }

    const handlePlaceReviewed = () => {
        showUpdatePlaceReviewedToast(onPlaceReviewed)
    }

    const handleLocationUpdated = (latitude, longitude) => {
        showUpdatePlaceLocationToast(() => onLocationChanged(latitude, longitude))
    }

    useEffect(() => {
        if (progress === 100) {
            handlePlaceReviewed()
        }
    }, [progress])

    const handlePhotosAdded = () => {
        const onlineAgents = agents.filter(agent => agent.lastSeen + agentOnlineStatusThresholdSeconds > Date.now() / 1000).map(agent => ({ id: agent.id, name: agent.name }))
        showUploadPhotosToast(onlineAgents, (date, path, agentId, sendNotification, mainPhotoPosition) => {
            const placeDate = place.getDate(parseISO(date))
            const timestamp = Math.floor(getTime(parseISO(date)) / 1000)
            if (!place.isPermanent() && !placeDate) {
                return Promise.reject("Unable to upload photos for the regular place for the date that does not exist.")
            }
            return onPhotosAdded(agentId, place.id, place.name, path, sendNotification, placeDate?.album?.id, timestamp, mainPhotoPosition)
        })
    }

    const handleAddressChanged = () => {
        showUpdatePlaceAddressToast(place, onAddressChanged)
    }

    return place ? (
        <>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                <div className="relative mx-2 sm:mx-0 text-gray-700 leading-relaxed">
                    <p className="text-justify">
                        {place.excerpt}
                    </p>
                    {!!(onAddressChanged || onExcerptRefreshed || onExcerptChanged || onPhotosAdded) && (
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
                    placeMainCategorySelector={place => place?.getCategory("mostSpecificWithMetadata")}
                    onRightClick={onLocationChanged && handleLocationUpdated} />
            </div>
            {hasRole(UserRole.PortalWarningRead) && onPlaceReviewed && place.dates?.map(date => date.album)?.filter(Boolean)?.some(album => !album.reviewed) && (
                <div className="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 mt-4 mb-6">
                    <div className="flex items-center justify-between mb-3">
                        <div className="flex items-center space-x-2 text-orange-600">
                            <TriangleAlert className="w-6 h-6 shrink-0 mb-[1px]" />
                            <span className="text-xl font-medium">
                                Nutná revize
                            </span>
                        </div>
                        <div className="w-1/2 bg-gray-100 rounded-full h-2 overflow-hidden">
                            <div
                                className="h-2 rounded-full bg-blue-600 transition-all duration-300"
                                style={{ width: `${progress}%` }} />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                        {checklistItems.map((label, index) => (
                            <label
                                key={index}
                                className="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-1 rounded">
                                <input
                                    type="checkbox"
                                    checked={!!checked[label]}
                                    onChange={() => handleChange(label)}
                                    className="accent-blue-600 w-4 h-4" />
                                <span className="text-sm text-gray-700">
                                    {label}
                                </span>
                            </label>
                        ))}
                    </div>
                </div>
            )}
        </>
    ) : (
        <div className="flex justify-center items-center min-h-[400px]">
            <TailSpin
                color="black"
                height={80}
                width={80} />
        </div>
    )
}