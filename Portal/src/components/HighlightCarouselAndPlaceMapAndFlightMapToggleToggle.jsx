import { useLayoutEffect, useRef, useState } from "react"
import { Map as MapIcon, Images } from "lucide-react"
import HighlightCarousel from "./HighlightCarousel.tsx"
import PlaceMap from "./PlaceMap.tsx"
import { getCurrentOrMaximumAllowedTimestamp } from "../utils/timeUtils.ts"
import PlaceMapAndFlightMapToggle from "./PlaceMapAndFlightMapToggle.tsx"

export default function HighlightCarouselAndPlaceMapAndFlightMapToggleToggle({ entity, places, flights, placeMainCategorySelector, airportMainCategorySelector, onPhotoReplaced, onPhotoCorrected,
    onHighlightRemoved, onMainHighlightUpdated, onHighlightQualityAttributesUpdated, onRightClick }) {
    const [showMap, setShowMap] = useState(false)
    const [height, setHeight] = useState(0)
    const carouselRef = useRef(null)

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

    if (entity && (!Array.isArray(entity.highlights) || entity.highlights.filter(highlight => highlight.photo.timestamp < getCurrentOrMaximumAllowedTimestamp()).length === 0)) {
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
                    highlights={entity && (entity.highlights ?? []).filter(highlight => highlight.photo.timestamp < getCurrentOrMaximumAllowedTimestamp())}
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
                    onClick={() => setShowMap(prev => !prev)}
                    className="absolute bottom-3 right-3 btn-chip-gray">
                    {showMap ? <Images size={16} /> : <MapIcon size={16} />}
                </button>
            )}
        </div>
    )
}
