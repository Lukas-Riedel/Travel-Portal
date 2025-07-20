import { useLayoutEffect, useRef, useState } from "react"
import { Map as MapIcon, Images } from "lucide-react"
import HighlightCarousel from "./HighlightCarousel"
import PlaceMap from "./PlaceMap"

export default function HighlightCarouselAndPlaceMapToggle({ entity, places, placeMainCategorySelector, onPhotoReplaced,
    onHighlightRemoved, onMainHighlightUpdated, onHighlightQualityAttributesUpdated }) {
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
        return (
            <div className="h-[365px] sm:h-[730px] my-4">
                <PlaceMap
                    places={places}
                    placeMainCategorySelector={placeMainCategorySelector} />
            </div>
        )
    }

    return (
        <div className="relative w-full my-4">
            <div
                ref={carouselRef}
                style={showMap ? { position: "absolute", left: "-9999px", top: 0, width: "100%" } : { width: "100%" }}>
                <HighlightCarousel
                    highlights={entity?.highlights}
                    onPhotoReplaced={onPhotoReplaced}
                    onHighlightRemoved={onHighlightRemoved}
                    onMainHighlightUpdated={onMainHighlightUpdated}
                    onHighlightQualityAttributesUpdated={onHighlightQualityAttributesUpdated} />
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
                    onClick={() => setShowMap(prev => !prev)}
                    className="absolute bottom-3 right-3 btn-chip-gray">
                    {showMap ? <Images size={16} /> : <MapIcon size={16} />}
                </button>
            )}
        </div>
    )
}
