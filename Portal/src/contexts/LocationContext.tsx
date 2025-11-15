import React, { createContext, useContext, useEffect, useState } from "react"
import type { UseLocationResult } from "../types/UseLocationResult.ts"
import type { Coordinates } from "../types/Coordinates.ts"
import { ONE_HOUR_SECONDS } from "../utils/timeUtils.ts"

const LocationContext = createContext<UseLocationResult | undefined>(undefined)

export function LocationProvider({ children }: { children: React.ReactNode }) {
    const [currentLocation, setCurrentLocation] = useState<Coordinates | null>(null)

    useEffect(() => {
        const watchId = navigator.geolocation.watchPosition(
            location => {
                setCurrentLocation(location.coords)
            },
            error => {
                console.error("Unable to obtain current location.", error)
            },
            {
                maximumAge: ONE_HOUR_SECONDS * 1000,
                enableHighAccuracy: false,
                timeout: 10 * 1000
            }
        )

        return () => {
            navigator.geolocation.clearWatch(watchId)
        }
    }, [])

    return (
        <LocationContext.Provider value={currentLocation}>
            {children}
        </LocationContext.Provider>
    )
}

export const useLocation = (): UseLocationResult => useContext(LocationContext)