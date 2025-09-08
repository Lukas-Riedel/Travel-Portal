import { createContext, useContext, useEffect, useState } from "react"

const LocationContext = createContext()

export function LocationProvider({ children }) {
    const [currentLocation, setCurrentLocation] = useState(null)

    useEffect(() => {
        navigator.geolocation.watchPosition(
            location => {
                setCurrentLocation({
                    latitude: location.coords.latitude,
                    longitude: location.coords.longitude
                })
            },
            error => {
                console.error("Geolocation error:", error)
            },
            {
                maximumAge: 15 * 60 * 1000, // 15 minutes
                enableHighAccuracy: false,
                timeout: 10 * 1000 // 10 seconds
            }
        )

    }, [])

    return (
        <LocationContext.Provider value={currentLocation}>
            {children}
        </LocationContext.Provider>
    )
}

export const useLocation = () => useContext(LocationContext)
