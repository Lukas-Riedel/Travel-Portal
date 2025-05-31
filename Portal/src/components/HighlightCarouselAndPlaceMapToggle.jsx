import { useEffect, useRef, useState } from "react"
import { Map, Images } from "lucide-react"
import HighlightCarousel from "./HighlightCarousel"
import PlaceMap from "./PlaceMap"

export default function HighlightCarouselAndPlaceMapToggle({ entity, places, placeMainCategorySelector, onHighlightRemoved, onMainHighlightUpdated }) {
    const [showMap, setShowMap] = useState(false)
    const [height, setHeight] = useState(0)
    const carouselRef = useRef(null)

    useEffect(() => {
        if (!carouselRef.current) {
            return
        }

        const updateHeight = () => setHeight(carouselRef.current.offsetHeight)
        updateHeight()

        window.addEventListener("resize", updateHeight)
        return () => window.removeEventListener("resize", updateHeight)
    }, [])

    return (
        <div className="relative">
            <div className="relative">
                <div
                    className={showMap ? "hidden" : ""}
                    ref={carouselRef}>
                    <HighlightCarousel
                        name={entity.name}
                        highlights={entity.highlights}
                        onHighlightRemoved={onHighlightRemoved}
                        onMainHighlightUpdated={onMainHighlightUpdated} />
                </div>
                <div
                    className={showMap ? "" : "hidden"}
                    style={{ height }}>
                    <PlaceMap
                        places={places}
                        placeMainCategorySelector={placeMainCategorySelector}
                    />
                </div>

                <button
                    onClick={() => setShowMap((prev) => !prev)}
                    className="absolute bottom-2 right-2 rounded-full bg-white/80 backdrop-blur-sm text-black shadow-md hover:bg-white transition-colors px-3 py-1 text-sm font-medium flex items-center space-x-2 z-10">
                    {showMap ? <Images size={16} /> : <Map size={16} />}
                </button>
            </div>
        </div>
    )
}
