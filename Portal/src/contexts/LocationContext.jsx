import { createContext, useContext, useEffect, useState } from "react"

const LocationContext = createContext()

export function LocationProvider({ children }) {
    const [currentLocation, setCurrentLocation] = useState(null)

    useEffect(() => {
        navigator.geolocation.getCurrentPosition(location => {
            setCurrentLocation({
                latitude: location.coords.latitude,
                longitude: location.coords.longitude
            })
        })
    }, [])

    return (
        <LocationContext.Provider value={currentLocation}>
            {children}
        </LocationContext.Provider>
    )
}

export const useLocation = () => useContext(LocationContext)
