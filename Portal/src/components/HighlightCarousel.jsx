import { useState, useEffect, useRef } from "react"
import { AnimatePresence, motion } from "framer-motion"
import { Pause, Play, Trash2, Star, SlidersVertical, Grid3x3 } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import showConfirmToast from "./ConfirmToast"
import { Grid, TailSpin } from "react-loader-spinner"
import showFormToast from "./FormToast"
import { format, fromUnixTime } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import SunCalc from "suncalc"
import { isDaylightSavingTime } from "../utils/helpers"
import { useConfiguration } from "../contexts/ConfigContext"

export default function HighlightCarousel({ place, highlights, onHighlightRemoved, onMainHighlightUpdated, onHighlightQualityAttributesUpdated }) {
    const { isAdmin } = useAuth()
    const configuration = useConfiguration()

    const [shuffledHighlights, setShuffledHighlights] = useState([])
    const [currentHighlightIndex, setCurrentHighlightIndex] = useState(0)
    const [isPaused, setIsPaused] = useState(isAdmin)
    const [showGrid, setShowGrid] = useState(false)
    const didShuffleRef = useRef(false)

    useEffect(() => {
        if (!highlights?.length) {
            didShuffleRef.current = false
            setShuffledHighlights([])
        }
        else if (!didShuffleRef.current) {
            setShuffledHighlights([...(highlights ?? [])].sort(() => Math.random() - 0.5))
            setCurrentHighlightIndex(0)
            didShuffleRef.current = true
        }
    }, [highlights])

    useEffect(() => {
        if (isPaused) {
            return
        }

        const interval = setInterval(() => setCurrentHighlightIndex(previous => (previous + 1) % shuffledHighlights.length), 7000)
        return () => clearInterval(interval)
    }, [shuffledHighlights, isPaused])

    const handleHighlightRemoved = () => {
        const highlight = shuffledHighlights[currentHighlightIndex]
        showConfirmToast("Opravdu chceš odstranit tento highlight?",
            "Highlight byl úspěšně odstraněn",
            "Nepodařilo se odstranit highlight",
            async () => {
                const newHighlights = [...shuffledHighlights]
                newHighlights.splice(currentHighlightIndex, 1)
                setShuffledHighlights(newHighlights)
                setCurrentHighlightIndex(previous => Math.max(0, Math.min(previous, newHighlights.length - 1)))
                return onHighlightRemoved(highlight.id)
            })
    }

    const handleMainHighlightUpdated = () => {
        const highlight = shuffledHighlights[currentHighlightIndex]
        showConfirmToast("Opravdu chceš nastavit tento highlight jako hlavní highlight?",
            "Hlavní highlight byl úspěšně aktualizován",
            "Nepodařilo se aktualizovat hlavní highlight",
            async () => onMainHighlightUpdated(highlight.id))
    }

    const handleHighlightQualityAttributesUpdated = () => {
        const highlight = shuffledHighlights[currentHighlightIndex]
        const timestamp = highlight.timestamp - (isDaylightSavingTime(highlight.timestamp, configuration?.homeLocation?.timezone) ? 0 : 3600)
        showFormToast(
            "Zadej nové atributy:",
            [
                {
                    label: "Kompozice", value: highlight.composition, required: true, type: "select", options: [
                        { id: null, name: "" },
                        { id: 5, name: "Nedostatečná" },
                        { id: 60, name: "Průměrná" },
                        { id: 100, name: "Kvalitní" }
                    ]
                },
                {
                    label: "Nebe", value: highlight.sky, required: true, type: "select", options: [
                        { id: null, name: "" },
                        { id: 10, name: "Jednolitá šedá" },
                        { id: 30, name: "Zataženo s výraznou strukturou mraků" },
                        { id: 50, name: "Oblačná s prosvítajícím sluncem" },
                        { id: 95, name: "Jasná" },
                        { id: 100, name: "Fotogenní mraky" }
                    ]
                },
                {
                    label: "Stíny", value: highlight.shadows, required: true, type: "select", options: [
                        { id: null, name: "" },
                        { id: 40, name: "Ploché (zataženo nebo polední světlo)" },
                        { id: 60, name: "Mírné (lehké modelování scény)" },
                        { id: 100, name: "Výrazné (boční světlo, plastika)" }
                    ]
                },
                {
                    label: "Okolnosti", value: highlight.circumstances, required: true, type: "select", options: [
                        { id: null, name: "" },
                        { id: 20, name: "Výrazně rušivé (lešení, davy, nepořádek)" },
                        { id: 70, name: "Rušivé (něco narušuje celkový dojem)" },
                        { id: 90, name: "Minimálně rušivé (drobná rušení)" },
                        { id: 100, name: "Bez rušivých prvků (čistá scéna)" }
                    ]
                },
                place && { label: "Čas pořízení:", value: format(toZonedTime(fromUnixTime(timestamp), place.timezone), "d.M.yyyy HH:mm"), required: true, disabled: true },
                place && { label: "Výška slunce:", value: ((SunCalc.getPosition(new Date(timestamp * 1000), place.latitude, place.longitude).altitude * 180) / Math.PI).toFixed(1) + "°", required: true, disabled: true }
            ],
            "Atributy highlightu byly úspěšně aktualizovány",
            "Nepodařilo se aktualizovat atributy highlightu",
            async (composition, sky, shadows, circumstances) => onHighlightQualityAttributesUpdated(highlight.id, composition, sky, shadows, circumstances)
        )
    }

    if (highlights && shuffledHighlights.length === 0) {
        return null
    }

    return shuffledHighlights.length > 0 ? (
        <div className="relative w-full [aspect-ratio:3/2] overflow-hidden rounded-xl shadow-lg my-4">
            <AnimatePresence mode="sync">
                <motion.img
                    key={currentHighlightIndex}
                    src={shuffledHighlights[currentHighlightIndex]?.url?.full ?? shuffledHighlights[currentHighlightIndex]?.url?.thumbnail}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    transition={{ duration: 1 }}
                    className="absolute inset-0 h-full w-full object-cover object-center" />
            </AnimatePresence>
            {showGrid && (
                <div className="absolute inset-0 pointer-events-none">
                    <div className="absolute inset-y-0 left-1/3 w-px bg-white/50" />
                    <div className="absolute inset-y-0 left-2/3 w-px bg-white/50" />
                    <div className="absolute inset-x-0 top-1/3 h-px bg-white/50" />
                    <div className="absolute inset-x-0 top-2/3 h-px bg-white/50" />
                    <div className="absolute inset-y-0 left-1/2 w-1 bg-white/75 -translate-x-1/2" />
                    <div className="absolute inset-x-0 top-1/2 h-1 bg-white/75 -translate-y-1/2" />
                </div>
            )}
            {shuffledHighlights.length > 1 && (
                <div className="absolute bottom-3 left-1/2 transform -translate-x-1/2 flex space-x-2">
                    {shuffledHighlights.map((_, index) => (
                        <button
                            key={index}
                            onClick={() => setCurrentHighlightIndex(index)}
                            className={`w-3 h-3 rounded-full ${index === currentHighlightIndex ? "bg-white" : "bg-white/50"}`}
                        ></button>
                    ))}
                </div>
            )}
            <div className="absolute top-3 right-3 flex space-x-2">
                {isAdmin && (
                    <button
                        onClick={() => setShowGrid(prev => !prev)}
                        className="btn-chip-gray">
                        {<Grid3x3 size={16} />}
                    </button>
                )}
                {onHighlightQualityAttributesUpdated && isAdmin && (
                    <button
                        onClick={handleHighlightQualityAttributesUpdated}
                        className="btn-chip-gray">
                        {<SlidersVertical size={16} />}
                    </button>
                )}
                {shuffledHighlights.length > 1 && (
                    <>
                        <button
                            onClick={() => setIsPaused(prev => !prev)}
                            className="btn-chip-gray">
                            {isPaused ? <Play size={16} /> : <Pause size={16} />}
                        </button>
                        {onMainHighlightUpdated && isAdmin && (
                            <button
                                onClick={handleMainHighlightUpdated}
                                className="btn-chip-gray">
                                <Star size={16} />
                            </button>
                        )}
                        {onHighlightRemoved && isAdmin && (
                            <button
                                onClick={handleHighlightRemoved}
                                className="btn-chip-gray">
                                <Trash2 size={16} />
                            </button>
                        )}
                    </>
                )}
            </div>
        </div>
    ) : (
        <div className="flex items-center justify-center w-full [aspect-ratio:3/2]">
            <TailSpin
                color="black"
                height={80}
                width={80} />
        </div>
    )
}