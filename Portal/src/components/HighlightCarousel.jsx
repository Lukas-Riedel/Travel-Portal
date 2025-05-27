import { useState, useEffect } from "react"
import { AnimatePresence, motion } from "framer-motion"
import { Pause, Play } from "lucide-react"

export default function HighlightCarousel({ name, highlights }) {
    const [shuffledHighlights] = useState(() => [...highlights].sort(() => Math.random() - 0.5))
    const [currentHighlightIndex, setCurrentHighlightIndex] = useState(0)
    const [isPaused, setIsPaused] = useState(false)

    useEffect(() => {
        if (isPaused) {
            return
        }

        const interval = setInterval(() => setCurrentHighlightIndex(previous => (previous + 1) % highlights.length), 7000)
        return () => clearInterval(interval)
    }, [shuffledHighlights, isPaused])

    if (shuffledHighlights.length === 0) {
        return null
    }

    return (
        <div className="relative w-full [aspect-ratio:3/2] overflow-hidden rounded-xl shadow-lg">
            <AnimatePresence mode="sync">
                <motion.img
                    key={currentHighlightIndex}
                    src={shuffledHighlights[currentHighlightIndex].url.full ?? shuffledHighlights[currentHighlightIndex].url.thumbnail}
                    alt={name}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: 1 }}
                    className="absolute inset-0 h-full w-full object-cover object-center" />
            </AnimatePresence>
            {shuffledHighlights.length > 1 && (
                <div>
                    <div className="absolute bottom-3 left-1/2 transform -translate-x-1/2 flex space-x-2">
                        {shuffledHighlights.map((_, index) => (
                            <button
                                key={index}
                                onClick={() => setCurrentHighlightIndex(index)}
                                className={`w-3 h-3 rounded-full ${index === currentHighlightIndex ? "bg-white" : "bg-white/50"}`}
                            ></button>
                        ))}
                    </div>
                    <button
                        onClick={() => setIsPaused(previous => !previous)}
                        className="absolute top-3 right-3 rounded-full bg-white/80 backdrop-blur-sm text-black shadow-md hover:bg-white transition-colors px-3 py-1 text-sm font-medium flex items-center space-x-2">
                        {isPaused ? <Play size={16} /> : <Pause size={16} />}
                    </button>
                </div>
            )}
        </div>
    )
}