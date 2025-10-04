import { useState, useEffect, useRef, useMemo } from "react"
import { AnimatePresence, motion } from "framer-motion"
import { Pause, Play, Trash2, Star, SlidersVertical, Grid3x3, Edit2 } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import showConfirmToast from "./ConfirmToast"
import { TailSpin } from "react-loader-spinner"
import showFormToast from "./FormToast"
import { format, fromUnixTime } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import { getOnlyElement, isDaylightSavingTime } from "../utils/helpers"
import { useConfiguration } from "../contexts/ConfigContext"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { useDevices } from "../hooks/useDevices"

const invalidPhotoId = "INVALID_PHOTO_ID"
const agentOnlineStatusThresholdSeconds = 60

export default function HighlightCarousel({ place, highlights, onPhotoReplaced, onHighlightRemoved, onMainHighlightUpdated, onHighlightQualityAttributesUpdated }) {
    const { isAdmin } = useAuth()
    const { configuration } = useConfiguration()
    const agents = useDevices({ type: "agent" })

    const [shuffledHighlights, setShuffledHighlights] = useState([])
    const [currentHighlightIndex, setCurrentHighlightIndex] = useState(0)
    const currentHighlightPlaces = useRegularPlaces({ photoId: shuffledHighlights[currentHighlightIndex]?.photo?.id ?? invalidPhotoId, include: "dates" })
    const currentHighlightAlbumId = useMemo(() => getOnlyElement(currentHighlightPlaces?.flatMap(place => place.dates)
        ?.map(date => date.album).filter(Boolean).map(album => album.id)), [currentHighlightPlaces])
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

    const handlePhotoReplaced = () => {
        showFormToast(
            "Zadej cestu k nové fotce:",
            [
                { label: "Cesta", required: true },
                { label: "Agent", required: true, type: "select", options: agents.filter(agent => agent.lastSeen + agentOnlineStatusThresholdSeconds > Date.now() / 1000).map(agent => ({ id: agent.id, name: agent.name })) }
            ],
            "Nahrazování fotky bude brzy zahájeno",
            "Při nahrazování fotky došlo k chybě",
            async (path, agentId) => onPhotoReplaced(agentId, place.id, currentHighlightAlbumId, place.name, shuffledHighlights[currentHighlightIndex].photo.id, path)
                .then(() => window.open(shuffledHighlights[currentHighlightIndex].photo.permalink, "_blank"))
        )
    }

    const handleHighlightRemoved = () => {
        const highlight = shuffledHighlights[currentHighlightIndex]
        showConfirmToast("Opravdu chceš odstranit tento highlight?",
            "Highlight byl úspěšně odstraněn",
            "Nepodařilo se odstranit highlight",
            async () => {
                return onHighlightRemoved(highlight.id).then(_ => {
                    const newHighlights = [...shuffledHighlights]
                    newHighlights.splice(currentHighlightIndex, 1)
                    setShuffledHighlights(newHighlights)
                    setCurrentHighlightIndex(previous => Math.max(0, Math.min(previous, newHighlights.length - 1)))
                })
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
        const timestamp = highlight.photo.timestamp - (isDaylightSavingTime(highlight.photo.timestamp, configuration?.homeLocation?.timezone) ? 0 : 3600)
        showFormToast(
            "Zadej nové atributy:",
            [
                {
                    label: "Kompozice", value: highlight.attributes?.composition, required: true, type: "select", options: [
                        { id: null, name: "" },
                        { id: 5, name: "Nedostatečná" },
                        { id: 60, name: "Průměrná" },
                        { id: 100, name: "Kvalitní" }
                    ]
                },
                {
                    label: "Obloha", value: highlight.attributes?.sky, required: true, type: "select", options: [
                        { id: null, name: "" },
                        { id: 10, name: "Jednolitá šedá" },
                        { id: 30, name: "Zataženo s výraznou strukturou mraků" },
                        { id: 50, name: "Oblačná s prosvítajícím sluncem" },
                        { id: 95, name: "Jasná" },
                        { id: 100, name: "Fotogenní mraky" }
                    ]
                },
                {
                    label: "Stíny", value: highlight.attributes?.shadows, required: true, type: "select", options: [
                        { id: null, name: "" },
                        { id: 35, name: "Silné protisvětlo (špatná denní doba)" },
                        { id: 40, name: "Ploché (zataženo nebo polední světlo)" },
                        { id: 60, name: "Mírné (lehké modelování scény)" },
                        { id: 100, name: "Výrazné (boční světlo, plastika)" }
                    ]
                },
                {
                    label: "Okolnosti", value: highlight.attributes?.circumstances, required: true, type: "select", options: [
                        { id: null, name: "" },
                        { id: 20, name: "Výrazně rušivé (lešení, davy, nepořádek)" },
                        { id: 70, name: "Rušivé (něco narušuje celkový dojem)" },
                        { id: 90, name: "Minimálně rušivé (drobná rušení)" },
                        { id: 100, name: "Bez rušivých prvků (čistá scéna)" }
                    ]
                },
                {
                    label: "Atmosféra", value: highlight.attributes?.atmosphere, required: true, type: "select", options: [
                        { id: null, name: "" },
                        { id: 30, name: "Znečištěný nebo zakalený vzduch (opar, smog, inverze)" },
                        { id: 80, name: "Mírný opar (snížená čirost, ale přijatelná)" },
                        { id: 95, name: "Čistý vzduch (dobrá viditelnost, přirozené barvy)" },
                        { id: 100, name: "Křišťálově čistý vzduch (výjimečně fotogenické podmínky)" }
                    ]
                },
                place && { label: "Čas pořízení:", value: format(toZonedTime(fromUnixTime(timestamp), place.timezone), "d.M.yyyy HH:mm"), required: true, disabled: true },
                highlight.photo.sunAltitude && { label: "Výška slunce:", value: highlight.photo.sunAltitude.toFixed(1) + "°", required: true, disabled: true }
            ],
            "Atributy highlightu byly úspěšně aktualizovány",
            "Nepodařilo se aktualizovat atributy highlightu",
            async (composition, sky, shadows, circumstances, atmosphere) =>
                onHighlightQualityAttributesUpdated(highlight.id, composition, sky, shadows, circumstances, atmosphere)
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
                {onPhotoReplaced && place && currentHighlightAlbumId && isAdmin && (
                    <button
                        onClick={handlePhotoReplaced}
                        className="btn-chip-gray">
                        <Edit2 size={16} />
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