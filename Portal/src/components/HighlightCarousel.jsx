import { useState, useEffect } from "react"
import { AnimatePresence, motion } from "framer-motion"
import { Pause, Play, Trash2, Star } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import showConfirmToast from "./ConfirmToast"

export default function HighlightCarousel({ name, highlights, onHighlightRemoved, onMainHighlightUpdated }) {
    const { isAdmin } = useAuth()
    const [shuffledHighlights, setShuffledHighlights] = useState(() => [...highlights].sort(() => Math.random() - 0.5))
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

    const handleHighlightRemoved = () => {
        showConfirmToast("Opravdu chceš odstranit tento highlight?",
            "Highlight byl úspěšně odstraněn",
            "Nepodařilo se odstranit highlight",
            async () => {
                const highlight = shuffledHighlights[currentHighlightIndex]
                const newHighlights = [...shuffledHighlights]
                newHighlights.splice(currentHighlightIndex, 1)
                setShuffledHighlights(newHighlights)
                setCurrentHighlightIndex(previous => Math.max(0, Math.min(previous, newHighlights.length - 1)))
                return onHighlightRemoved(highlight.id)
            })
    }

    const handleMainHighlightUpdated = () => {
        showConfirmToast("Opravdu chceš nastavit tento highlight jako hlavní highlight?",
            "Hlavní highlight byl úspěšně aktualizován",
            "Nepodařilo se aktualizovat hlavní highlight",
            async () => {
                const highlight = shuffledHighlights[currentHighlightIndex]
                return onMainHighlightUpdated(highlight.id)
            })
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
                <>
                    <div className="absolute bottom-3 left-1/2 transform -translate-x-1/2 flex space-x-2">
                        {shuffledHighlights.map((_, index) => (
                            <button
                                key={index}
                                onClick={() => setCurrentHighlightIndex(index)}
                                className={`w-3 h-3 rounded-full ${index === currentHighlightIndex ? "bg-white" : "bg-white/50"}`}
                            ></button>
                        ))}
                    </div>
                    <div className="absolute top-3 right-3 flex space-x-2">
                        <button
                            onClick={() => setIsPaused(prev => !prev)}
                            className="btn-chip-white">
                            {isPaused ? <Play size={16} /> : <Pause size={16} />}
                        </button>
                        {onMainHighlightUpdated && isAdmin() && (
                            <button
                                onClick={handleMainHighlightUpdated}
                                className="btn-chip-white">
                                <Star size={16} />
                            </button>
                        )}
                        {onHighlightRemoved && isAdmin() && (
                            <button
                                onClick={handleHighlightRemoved}
                                className="btn-chip-white">
                                <Trash2 size={16} />
                            </button>
                        )}
                    </div>
                </>
            )}
        </div>
    )
}