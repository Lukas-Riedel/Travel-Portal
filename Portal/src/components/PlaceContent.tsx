import { ImagePlus, LocationEdit, RefreshCcw, SquarePen, TriangleAlert } from "lucide-react"
import { useUserInput } from "../hooks/useUserInput.tsx"
import PlaceMap from "./PlaceMap.jsx"
import { TailSpin } from "react-loader-spinner"
import { getTime, parseISO } from "date-fns"
import { useDevices } from "../hooks/useDevices"
import { useEffect, useState } from "react"
import { DeviceType, UserRole } from "../types/CoreSwaggerTypes.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import type { Place } from "../classes/Place.ts"
import { isDeviceOnline } from "../utils/deviceUtils.ts"
import { useOnlineAgents } from "../hooks/useOnlineAgents.ts"
import { InternalCategoryCategory } from "../types/InternalCategoryCategory.ts"

interface PlaceContentProps {
    place: Place | null
    onPhotosAdded: (agentId: string, placeId: string, placeName: string, path: string, sendNotification: boolean, albumId?: string, timestamp?: number, mainPhotoPosition?: number) => Promise<void>
    onExcerptChanged: (excerpt: string) => Promise<Place>
    onAddressChanged: (address: string) => Promise<Place>
    onExcerptRefreshed: () => Promise<Place>
    onLocationChanged: (latitude: number, longitude: number) => Promise<Place>
}

export default function PlaceContent({ place, onPhotosAdded, onExcerptChanged, onAddressChanged, onExcerptRefreshed, onLocationChanged }: PlaceContentProps) {
    const onlineAgents = useOnlineAgents()
    const { showUploadPhotosToast, showRefreshPlaceExcerptToast, showUpdatePlaceLocationToast, showUpdatePlaceExcerptToast, showUpdatePlaceAddressToast } = usePredefinedUserInput()

    const handleExcerptChanged = () => {
        if (onExcerptChanged) {
            showUpdatePlaceExcerptToast(place, onExcerptChanged)
        }
    }

    const handleExcerptRefreshed = () => {
        if (onExcerptRefreshed) {
            showRefreshPlaceExcerptToast(onExcerptRefreshed)
        }
    }

    const handleLocationUpdated = (latitude: number, longitude: number) => {
        if (onLocationChanged) {
            showUpdatePlaceLocationToast(() => onLocationChanged(latitude, longitude))
        }
    }

    const handlePhotosAdded = () => {
        if (onlineAgents && onPhotosAdded) {
            showUploadPhotosToast(onlineAgents, (date, path, agentId, sendNotification, mainPhotoPosition) => {
                const placeDate = place.getDate(parseISO(date))
                const timestamp = Math.floor(getTime(parseISO(date)) / 1000)
                if (!place.isPermanent() && !placeDate) {
                    return Promise.reject("Unable to upload photos for the regular place for the date that does not exist.")
                }

                return onPhotosAdded(agentId, place.id, place.name, path, sendNotification, placeDate.album?.id, timestamp, mainPhotoPosition)
            })
        }
    }

    const handleAddressChanged = () => {
        if (onAddressChanged) {
            showUpdatePlaceAddressToast(place, onAddressChanged)
        }
    }

    if (!place) {
        return (
            <div className="flex justify-center items-center min-h-[400px]">
                <TailSpin
                    color="black"
                    height={80}
                    width={80} />
            </div>
        )
    }

    return (
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
                placeMainCategorySelector={place => place.getCategory(InternalCategoryCategory.MostSpecificWithMetadata)}
                onRightClick={onLocationChanged && ((latitude, longitude) => Promise.resolve(handleLocationUpdated(latitude, longitude)))} />
        </div>
    )
}