import { useState, useEffect } from "react"
import { AnimatePresence, motion } from "framer-motion"

export default function HighlightCarousel({ name, highlights }) {
    const [currentHighlightIndex, setCurrentHighlightIndex] = useState(0)
    useEffect(() => {
        const interval = setInterval(() => setCurrentHighlightIndex(previous => (previous + 1) % highlights.length), 7000)
        return () => clearInterval(interval)
    }, [])

    if (highlights.length === 0) {
        return null
    }

    return (
        <div className="relative w-full aspect-video overflow-hidden rounded-xl shadow-lg">
            <AnimatePresence mode="popLayout">
                <motion.img
                    key={currentHighlightIndex}
                    src={highlights[currentHighlightIndex].url.full ?? highlights[currentHighlightIndex].url.thumbnail}
                    alt={name}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: 1 }}
                    className="absolute inset-0 h-full w-full object-cover"/>
            </AnimatePresence>
            {highlights.length > 1 && (
                <div className="absolute bottom-3 left-1/2 transform -translate-x-1/2 flex space-x-2">
                    {highlights.map((_, index) => (
                        <button
                            key={index}
                            onClick={() => setCurrentHighlightIndex(index)}
                            className={`w-3 h-3 rounded-full ${index === currentHighlightIndex ? "bg-white" : "bg-white/50"}`}
                        ></button>
                    ))}
                </div>
            )}
        </div>
    )
}