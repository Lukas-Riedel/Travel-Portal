import { useEffect, useRef, useState } from "react"
import { Map, Images } from "lucide-react"
import HighlightCarousel from "./HighlightCarousel"
import PlaceMap from "./PlaceMap"

export default function HighlightCarouselAndPlaceMapToggle({ entity, places, placeMainCategorySelector, onHighlightRemoved, onMainHighlightUpdated }) {
    const [showMap, setShowMap] = useState(true)
    const [height, setHeight] = useState(0)
    const carouselRef = useRef(null)

    useEffect(() => {
        const updateHeight = () => {
            if (!carouselRef.current) {
                return
            }

            const wasHidden = carouselRef.current.classList.contains("hidden")
            if (wasHidden) {
                carouselRef.current.classList.remove("hidden")
            }

            const newHeight = carouselRef.current.offsetHeight
            if (newHeight > 0) {
                setHeight(newHeight)
            }

            if (wasHidden) {
                carouselRef.current.classList.add("hidden")
            }
        }

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
                    className="absolute bottom-3 right-3 btn-chip-white">
                    {showMap ? <Images size={16} /> : <Map size={16} />}
                </button>
            </div>
        </div>
    )
}
