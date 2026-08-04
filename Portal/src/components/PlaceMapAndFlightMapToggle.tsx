import { useState } from "react"
import { MapPin, Plane } from "lucide-react"
import type { Place } from "../classes/Place"
import type { Airport, Category, Flight } from "../types/CoreSwaggerTypes"
import PlaceMap from "./PlaceMap"
import FlightMap from "./FlightMap"

interface PlaceMapAndFlightMapToggleProps {
    places: Place[] | null
    flights: Flight[] | null
    placeMainCategorySelector: (place: Place) => Category
    airportMainCategorySelector: (airport: Airport) => Category | null
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
                    flights={flights}
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
                    className="absolute bottom-3 left-3 btn-chip-gray">
                    {showFlightMap ? <MapPin size={16} /> : <Plane size={16} />}
                </button>
            )}
        </div>
    )
}