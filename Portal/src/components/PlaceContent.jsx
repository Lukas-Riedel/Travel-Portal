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

export default function PlaceContent({ place, onPhotosAdded, onExcerptChanged, onAddressChanged, onExcerptRefreshed, onLocationChanged }) {
    const agents = useDevices({ type: "agent" })
    const { showUploadPhotosToast, showRefreshPlaceExcerptToast, showUpdatePlaceLocationToast, showUpdatePlaceExcerptToast, showUpdatePlaceAddressToast } = usePredefinedUserInput()

    const handleExcerptChanged = () => {
        showUpdatePlaceExcerptToast(place, onExcerptChanged)
    }

    const handleExcerptRefreshed = () => {
        showRefreshPlaceExcerptToast(onExcerptRefreshed)
    }

    const handleLocationUpdated = (latitude, longitude) => {
        showUpdatePlaceLocationToast(() => onLocationChanged(latitude, longitude))
    }

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
    ) : (
        <div className="flex justify-center items-center min-h-[400px]">
            <TailSpin
                color="black"
                height={80}
                width={80} />
        </div>
    )
}