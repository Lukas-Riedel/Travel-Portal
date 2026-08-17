import { useLayoutEffect, useMemo, useRef, useState } from "react"
import { Map as MapIcon, Images } from "lucide-react"
import HighlightCarousel from "./HighlightCarousel.tsx"
import { getCurrentOrMaximumAllowedTimestamp } from "../utils/timeUtils.ts"
import PlaceMapAndFlightMapToggle from "./PlaceMapAndFlightMapToggle.tsx"
import type { Highlightable } from "../types/Highlightable.ts"
import type { Place } from "../classes/Place.ts"
import type { Airport, Category, Flight, Highlight } from "../types/CoreSwaggerTypes.ts"

interface HighlightCarouselAndPlaceMapAndFlightMapToggleToggleProps {
    entity: Highlightable | null
    places: Place[] | null
    flights?: Flight[] | null
    placeMainCategorySelector: (place: Place) => Category
    airportMainCategorySelector?: (airport: Airport) => Category | null
    onPhotoReplaced?: (agentId: string, placeId: string, albumId: string, placeName: string, photoId: string, path: string, sendNotification: boolean) => Promise<void>
    onPhotoCorrected?: (placeId: string, albumId: string, filename: string, base64Data: string, photoId: string) => Promise<Highlight>
    onHighlightRemoved?: (highlightId: string) => Promise<void>
    onMainHighlightUpdated?: (highlightId: string) => Promise<Highlightable>
    onHighlightQualityAttributesUpdated?: (highlightId: string, composition: number | null, sky: number | null, shadows: number | null, circumstances: number | null, atmosphere: number | null, impression: number | null) => Promise<Highlight>
    onRightClick?: (latitude: number, longitude: number) => Promise<void>
}

export default function HighlightCarouselAndPlaceMapAndFlightMapToggleToggle({ entity, places, flights, placeMainCategorySelector, airportMainCategorySelector, onPhotoReplaced, onPhotoCorrected, onHighlightRemoved, onMainHighlightUpdated, onHighlightQualityAttributesUpdated, onRightClick }: HighlightCarouselAndPlaceMapAndFlightMapToggleToggleProps) {
    const [showMap, setShowMap] = useState(false)
    const [height, setHeight] = useState(0)
    const carouselRef = useRef<HTMLDivElement | null>(null)

    useLayoutEffect(() => {
        if (!carouselRef.current) {
            return
        }

        const width = carouselRef.current.offsetWidth
        const computedHeight = Math.floor((width * 2) / 3)

        if (computedHeight > 0) {
            setHeight(computedHeight)
        }
    }, [])

    const filteredHighlights = useMemo(() => entity?.highlights?.filter(highlight => highlight.photo.timestamp < getCurrentOrMaximumAllowedTimestamp()) ?? [], [entity?.highlights])

    if (entity && (!Array.isArray(entity.highlights) || filteredHighlights.length === 0)) {
        return (
            <div className="h-[365px] sm:h-[730px] my-4">
                <PlaceMapAndFlightMapToggle
                    places={places}
                    flights={flights}
                    placeMainCategorySelector={placeMainCategorySelector}
                    airportMainCategorySelector={airportMainCategorySelector}
                    onRightClick={onRightClick} />
            </div>
        )
    }

    return (
        <div className="relative w-full my-4">
            <div
                ref={carouselRef}
                style={showMap ? { position: "absolute", left: "-9999px", top: 0, width: "100%" } : { width: "100%" }}>
                <HighlightCarousel
                    place={null}
                    highlights={entity && filteredHighlights}
                    onPhotoReplaced={onPhotoReplaced}
                    onPhotoCorrected={onPhotoCorrected}
                    onHighlightRemoved={onHighlightRemoved}
                    onMainHighlightUpdated={onMainHighlightUpdated}
                    onHighlightQualityAttributesUpdated={onHighlightQualityAttributesUpdated} />
            </div>
            {showMap && (
                <div style={{ height, width: "100%" }}>
                    <PlaceMapAndFlightMapToggle
                        places={places}
                        flights={flights}
                        placeMainCategorySelector={placeMainCategorySelector}
                        airportMainCategorySelector={airportMainCategorySelector}
                        onRightClick={onRightClick} />
                </div>
            )}
            {entity && places && (
                <button
                    onClick={() => setShowMap(previous => !previous)}
                    className="absolute bottom-3 right-3 btn-chip-gray">
                    {showMap ? (
                        <Images size={16} />
                    ) : (
                        <MapIcon size={16} />
                    )}
                </button>
            )}
        </div>
    )
}
