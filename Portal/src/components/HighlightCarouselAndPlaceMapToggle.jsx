import { useLayoutEffect, useRef, useState } from "react"
import { Map as MapIcon, Images } from "lucide-react"
import HighlightCarousel from "./HighlightCarousel"
import PlaceMap from "./PlaceMap"

export default function HighlightCarouselAndPlaceMapToggle({ entity, places, placeMainCategorySelector, onHighlightRemoved, onMainHighlightUpdated }) {
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

    if (entity && (!Array.isArray(entity.highlights) || entity.highlights.length === 0)) {
        return null
    }

    return (
        <div className="relative w-full">
            <div
                ref={carouselRef}
                style={showMap ? { position: "absolute", left: "-9999px", top: 0, width: "100%" } : { width: "100%" }}>
                <HighlightCarousel
                    highlights={entity?.highlights}
                    onHighlightRemoved={onHighlightRemoved}
                    onMainHighlightUpdated={onMainHighlightUpdated} />
            </div>
            {showMap && (
                <div style={{ height, width: "100%" }}>
                    <PlaceMap
                        places={places}
                        placeMainCategorySelector={placeMainCategorySelector} />
                </div>
            )}

            {entity && places && (
                <button
                    onClick={() => setShowMap((prev) => !prev)}
                    className="absolute bottom-3 right-3 btn-chip-white">
                    {showMap ? <Images size={16} /> : <MapIcon size={16} />}
                </button>
            )}
        </div>
    )
}
