import { MapPin, Plane } from "lucide-react"
import { useState } from "react"

import type { Place } from "../types/CoreSwaggerTypes.ts"
import type { Airport, Category, Flight } from "../types/CoreSwaggerTypes"
import FlightMap from "./FlightMap"
import PlaceMap from "./PlaceMap"

interface PlaceMapAndFlightMapToggleProps {
    places: Place[] | null
    flights?: Flight[] | null
    placeMainCategorySelector?: (place: Place) => Category | null
    airportMainCategorySelector?: (airport: Airport) => Category | null
    onRightClick?: (latitude: number, longitude: number) => Promise<void>
}

export default function PlaceMapAndFlightMapToggle({ places, flights, placeMainCategorySelector, airportMainCategorySelector, onRightClick }: PlaceMapAndFlightMapToggleProps) {
    const [showFlightMap, setShowFlightMap] = useState(false)

    const hasPlaces = Array.isArray(places) && places.length > 0
    const hasFlights = Array.isArray(flights) && flights.length > 0

    const canToggle = hasPlaces && hasFlights
    const shouldRenderFlightMap = canToggle ? showFlightMap : (!hasPlaces && hasFlights)

    return (
        <div className="relative w-full h-full">
            {shouldRenderFlightMap ? (
                <FlightMap
                    flights={flights ?? null}
                    airportMainCategorySelector={airportMainCategorySelector} />
            ) : (
                <PlaceMap
                    places={places}
                    placeMainCategorySelector={placeMainCategorySelector}
                    onRightClick={onRightClick} />
            )}
            {canToggle && (
                <button
                    onClick={() => setShowFlightMap(prev => !prev)}
                    className="absolute bottom-3 left-3 btn-chip">
                    {showFlightMap ? <MapPin size={16} /> : <Plane size={16} />}
                </button>
            )}
        </div>
    )
}