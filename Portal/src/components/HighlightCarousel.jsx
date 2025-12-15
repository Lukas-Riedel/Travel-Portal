import { useState, useEffect, useMemo, useCallback } from "react"
import { AnimatePresence, motion } from "framer-motion"
import { Pause, Play, Trash2, Star, SlidersVertical, Edit2, Plus, Upload, Check } from "lucide-react"
import { useAuth } from "../contexts/AuthContext"
import { useUserInput } from "../hooks/useUserInput.tsx"
import { TailSpin } from "react-loader-spinner"
import { format, fromUnixTime } from "date-fns"
import { toZonedTime } from "date-fns-tz"
import { getOnlyElement, isDaylightSavingTime } from "../utils/helpers"
import { useConfiguration } from "../contexts/ConfigContext"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { useDevices } from "../hooks/useDevices"
import Cropper from "react-easy-crop"
import Slider from "../components/Slider"
import { listPlaceAlbumPhotos } from "../clients/coreClient"
import piexif from "piexifjs"
import { v4 as uuidv4 } from "uuid"

const invalidPhotoId = "d4cbc2ec-1dd2-4f57-87e6-ae12f197aa5c" // TODO: Resolve in a better way.
const agentOnlineStatusThresholdSeconds = 60

const defaultRotation = 0
const defaultZoom = 1
const defaultXPosition = 0
const defaultYPosition = 0

export default function HighlightCarousel({ place, highlights, onPhotoReplaced, onPhotoCorrected, onHighlightRemoved, onMainHighlightUpdated, onHighlightQualityAttributesUpdated, onHighlightCreated }) {
    const { isAdmin } = useAuth()
    const { configuration } = useConfiguration()
    const agents = useDevices({ type: "agent" })
    const { showConfirmToast, showFormToast } = useUserInput()

    const [shuffledHighlights, setShuffledHighlights] = useState([])
    const [currentHighlightIndex, setCurrentHighlightIndex] = useState(0)
    const { places: currentHighlightPlaces } = useRegularPlaces({ photoId: shuffledHighlights[currentHighlightIndex]?.photo?.id ?? invalidPhotoId, include: ["dates"] })
    const currentHighlightAlbumId = useMemo(() => getOnlyElement(currentHighlightPlaces?.flatMap(place => place.dates)
        ?.map(date => date.album).filter(Boolean).map(album => album.id)), [currentHighlightPlaces])
    const [isPaused, setIsPaused] = useState(isAdmin)
    const [showEditor, setShowEditor] = useState(false)
    const [crop, setCrop] = useState({ x: defaultXPosition, y: defaultYPosition })
    const [zoom, setZoom] = useState(defaultZoom)
    const [rotation, setRotation] = useState(defaultRotation)
    const [croppedAreaPixels, setCroppedAreaPixels] = useState(null)
    const [currentHighlightReferencePhotoUrl, setCurrentHighlightReferencePhotoUrl] = useState(null)

    useEffect(() => {
        const fetchAndSetPhotoUrl = async photoId => {
            if (place?.id && currentHighlightAlbumId) {
                const photos = await listPlaceAlbumPhotos(place.id, currentHighlightAlbumId)
                // TODO: Introduce a new endpoint for obtaining a photo based on its identifier
                const photo = photos.find(photo => photo.id === photoId)
                setCurrentHighlightReferencePhotoUrl(photo?.url + "=d")
            }
        }

        setCurrentHighlightReferencePhotoUrl(null)
        fetchAndSetPhotoUrl(shuffledHighlights[currentHighlightIndex]?.photo?.id)
    }, [place?.id, currentHighlightAlbumId, shuffledHighlights, currentHighlightIndex])

    const onCropComplete = useCallback((_, croppedAreaPixels) => {
        setCroppedAreaPixels(croppedAreaPixels)
    }, [])

    useEffect(() => {
        if (!isAdmin) {
            setShuffledHighlights([...(highlights ?? [])].sort(() => Math.random() - 0.5))
        }
        else {
            setShuffledHighlights(highlights ?? [])
        }
    }, [highlights])

    useEffect(() => {
        if (isPaused) {
            return
        }

        const interval = setInterval(() => setCurrentHighlightIndex(previous => (previous + 1) % shuffledHighlights.length), 7000)
        return () => clearInterval(interval)
    }, [shuffledHighlights, isPaused])

    const handleHighlightCreated = () => {
        const highlight = shuffledHighlights[currentHighlightIndex]
        showConfirmToast(
            "Opravdu chceš přidat tento highlight?",
            async () => {
                return onHighlightCreated(highlight.photo.id).then(_ => {
                    const newHighlights = [...shuffledHighlights]
                    newHighlights.splice(currentHighlightIndex, 1)
                    setShuffledHighlights(newHighlights)
                    setCurrentHighlightIndex(previous => Math.max(0, Math.min(previous, newHighlights.length - 1)))
                })
            }),
            "Highlight byl úspěšně přidán",
            "Nepodařilo se přidat highlight"
    }

    const handlePhotoReplaced = () => {
        showFormToast(
            "Zadej cestu k nové fotce:",
            [
                { label: "Cesta", required: true },
                { label: "Agent", required: true, type: "select", options: agents.filter(agent => agent.lastSeen + agentOnlineStatusThresholdSeconds > Date.now() / 1000).map(agent => ({ id: agent.id, name: agent.name })) }
            ],
            async (path, agentId) => onPhotoReplaced(agentId, place.id, currentHighlightAlbumId, place.name, shuffledHighlights[currentHighlightIndex].photo.id, path)
                .then(() => window.open(shuffledHighlights[currentHighlightIndex].photo.permalink, "_blank")),
            "Nahrazování fotky bude brzy zahájeno",
            "Při nahrazování fotky došlo k chybě"
        )
    }

    const handlePhotoCorrected = () => {
        showConfirmToast(
            "Opravdu chceš upravit tento highlight?",
            async () => getCroppedImg(currentHighlightReferencePhotoUrl, croppedAreaPixels, rotation)
                .then(base64Data => onPhotoCorrected(place.id, currentHighlightAlbumId, uuidv4() + ".jpg", base64Data, shuffledHighlights[currentHighlightIndex].photo.id))
                .then(() => window.open(shuffledHighlights[currentHighlightIndex].photo.permalink, "_blank")),
            "Highlight byl úspěšně upraven",
            "Nepodařilo se upravit highlight"
        )
    }

    const handleHighlightRemoved = () => {
        const highlight = shuffledHighlights[currentHighlightIndex]
        showConfirmToast(
            "Opravdu chceš odstranit tento highlight?",
            async () => {
                return onHighlightRemoved(highlight.id).then(_ => {
                    const newHighlights = [...shuffledHighlights]
                    newHighlights.splice(currentHighlightIndex, 1)
                    setShuffledHighlights(newHighlights)
                    setCurrentHighlightIndex(previous => Math.max(0, Math.min(previous, newHighlights.length - 1)))
                })
            }),
            "Highlight byl úspěšně odstraněn",
            "Nepodařilo se odstranit highlight"
    }

    const handleMainHighlightUpdated = () => {
        const highlight = shuffledHighlights[currentHighlightIndex]
        showConfirmToast(
            "Opravdu chceš nastavit tento highlight jako hlavní highlight?",
            async () => onMainHighlightUpdated(highlight.id)),
            "Hlavní highlight byl úspěšně aktualizován",
            "Nepodařilo se aktualizovat hlavní highlight"
    }

    const handleHighlightQualityAttributesUpdated = () => {
        const highlight = shuffledHighlights[currentHighlightIndex]
        const timestamp = highlight.photo.timestamp - (isDaylightSavingTime(highlight.photo.timestamp, configuration?.homeLocation?.timezone) ? 0 : 3600)
        showFormToast(
            "Zadej nové atributy:",
            [
                {
                    label: "Kompozice", defaultValue: highlight.attributes?.composition, required: true, type: "select", options: [
                        { id: 5, name: "Nedostatečná" },
                        { id: 60, name: "Průměrná" },
                        { id: 100, name: "Kvalitní" }
                    ]
                },
                {
                    label: "Obloha", defaultValue: highlight.attributes?.sky, required: true, type: "select", options: [
                        { id: 10, name: "Jednolitá šedá" },
                        { id: 30, name: "Zataženo s výraznou strukturou mraků" },
                        { id: 50, name: "Oblačná s prosvítajícím sluncem" },
                        { id: 95, name: "Jasná" },
                        { id: 100, name: "Fotogenní mraky" }
                    ]
                },
                {
                    label: "Stíny", defaultValue: highlight.attributes?.shadows, required: true, type: "select", options: [
                        { id: 35, name: "Silné protisvětlo (špatná denní doba)" },
                        { id: 40, name: "Ploché (zataženo nebo polední světlo)" },
                        { id: 60, name: "Mírné (lehké modelování scény)" },
                        { id: 100, name: "Výrazné (boční světlo, plastika)" }
                    ]
                },
                {
                    label: "Okolnosti", defaultValue: highlight.attributes?.circumstances, required: true, type: "select", options: [
                        { id: 20, name: "Výrazně rušivé (lešení, davy, nepořádek)" },
                        { id: 70, name: "Rušivé (něco narušuje celkový dojem)" },
                        { id: 90, name: "Minimálně rušivé (drobná rušení)" },
                        { id: 100, name: "Bez rušivých prvků (čistá scéna)" }
                    ]
                },
                {
                    label: "Atmosféra", value: highlight.attributes?.atmosphere, required: true, type: "select", options: [
                        { id: 30, name: "Znečištěný nebo zakalený vzduch (opar, smog, inverze)" },
                        { id: 80, name: "Mírný opar (snížená čirost, ale přijatelná)" },
                        { id: 95, name: "Čistý vzduch (dobrá viditelnost, přirozené barvy)" },
                        { id: 100, name: "Křišťálově čistý vzduch (výjimečně fotogenické podmínky)" }
                    ]
                },
                place && { label: "Čas pořízení:", defaultValue: format(toZonedTime(fromUnixTime(timestamp), place.timezone), "d.M.yyyy HH:mm"), required: true, disabled: true },
                highlight.photo.sunAltitude && { label: "Výška slunce:", defaultValue: highlight.photo.sunAltitude.toFixed(1) + "°", required: true, disabled: true }
            ],
            async (composition, sky, shadows, circumstances, atmosphere) =>
                onHighlightQualityAttributesUpdated(highlight.id, composition, sky, shadows, circumstances, atmosphere),
            "Atributy highlightu byly úspěšně aktualizovány",
            "Nepodařilo se aktualizovat atributy highlightu"
        )
    }

    if (highlights && shuffledHighlights.length === 0) {
        return null
    }

    return shuffledHighlights.length > 0 ? (
        <div className="relative w-full [aspect-ratio:3/2] overflow-hidden rounded-xl shadow-lg my-4">
            {showEditor ? (
                <Cropper
                    image={currentHighlightReferencePhotoUrl}
                    crop={crop}
                    zoom={zoom}
                    rotation={rotation}
                    aspect={3 / 2}
                    onCropChange={setCrop}
                    onZoomChange={setZoom}
                    onRotationChange={setRotation}
                    onCropComplete={onCropComplete} />
            ) : (
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
            )}
            {showEditor && (
                <>
                    <div className="absolute z-50 top-0 left-0 flex space-x-2 bg-white/10 backdrop-blur-md rounded-br-xl shadow-md p-3">
                        <Slider
                            name="Rotace"
                            value={rotation}
                            defaultValue={defaultRotation}
                            valueFormatter={value => (Math.round(10 * value) / 10) + "°"}
                            step={0.05}
                            minValue={-15}
                            maxValue={15}
                            onValueChanged={setRotation} />
                        <Slider
                            name="Zoom"
                            value={zoom}
                            defaultValue={defaultZoom}
                            valueFormatter={value => Math.round(100 * value) + "%"}
                            step={0.01}
                            minValue={1}
                            maxValue={3}
                            onValueChanged={setZoom} />
                        <Slider
                            name="Osa X"
                            value={crop.x}
                            defaultValue={defaultXPosition}
                            valueFormatter={value => Math.round(value)}
                            minValue={-720}
                            maxValue={720}
                            onValueChanged={x => setCrop({ x: x, y: crop.y })} />
                        <Slider
                            name="Osa Y"
                            value={crop.y}
                            defaultValue={defaultYPosition}
                            valueFormatter={value => Math.round(value)}
                            minValue={-480}
                            maxValue={480}
                            onValueChanged={y => setCrop({ x: crop.x, y: y })} />
                    </div>
                    <div className="absolute inset-0 pointer-events-none">
                        <div className="absolute inset-y-0 left-1/2 w-1 bg-white/75 -translate-x-1/2" />
                        <div className="absolute inset-x-0 top-1/2 h-1 bg-white/75 -translate-y-1/2" />
                    </div>
                </>
            )}
            {shuffledHighlights.length > 1 && (
                <div className="absolute bottom-3 left-1/2 transform -translate-x-1/2 flex space-x-2">
                    {shuffledHighlights.map((_, index) => (
                        <button
                            key={index}
                            onClick={() => setCurrentHighlightIndex(index)}
                            className={`w-3 h-3 rounded-full ${index === currentHighlightIndex ? "bg-white" : "bg-white/50"}`} />
                    ))}
                </div>
            )}
            <div className="absolute top-3 right-3 flex space-x-2">
                {onHighlightCreated && isAdmin && (
                    <button
                        onClick={handleHighlightCreated}
                        className="btn-chip-gray">
                        <Plus size={16} />
                    </button>
                )}
                {isAdmin && showEditor && (rotation !== defaultRotation || zoom !== defaultZoom || crop.x !== defaultXPosition || crop.y !== defaultYPosition) && (
                    <button
                        onClick={handlePhotoCorrected}
                        className="btn-chip-gray">
                        <Check size={16} />
                    </button>
                )}
                {onPhotoCorrected && isAdmin && currentHighlightReferencePhotoUrl && (
                    <button
                        onClick={() => setShowEditor(prev => !prev)}
                        className="btn-chip-gray">
                        <Edit2 size={16} />
                    </button>
                )}
                {onHighlightQualityAttributesUpdated && isAdmin && (
                    <button
                        onClick={handleHighlightQualityAttributesUpdated}
                        className="btn-chip-gray">
                        <SlidersVertical size={16} />
                    </button>
                )}
                {onPhotoReplaced && place && currentHighlightAlbumId && isAdmin && (
                    <button
                        onClick={handlePhotoReplaced}
                        className="btn-chip-gray">
                        <Upload size={16} />
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

const createImage = url =>
    new Promise((resolve, reject) => {
        const image = new Image()
        image.addEventListener("load", () => resolve(image))
        image.addEventListener("error", error => reject(error))
        image.setAttribute("crossOrigin", "anonymous")
        image.src = url
    })

const getCroppedImg = async (imageSrc, pixelCrop, rotation) => {
    const response = await fetch(imageSrc)
    const arrayBuffer = await response.arrayBuffer()
    const bytes = new Uint8Array(arrayBuffer)
    let binary = ""
    const chunkSize = 0x8000
    for (let i = 0; i < bytes.length; i += chunkSize) {
        const chunk = bytes.subarray(i, i + chunkSize)
        binary += String.fromCharCode(...chunk)
    }
    const base64 = btoa(binary)

    const exifObj = piexif.load("data:image/jpeg;base64," + base64)

    const image = await createImage(imageSrc)
    const canvas = document.createElement("canvas")
    const ctx = canvas.getContext("2d")

    const safeArea = Math.max(image.naturalWidth, image.naturalHeight) * 2
    canvas.width = safeArea
    canvas.height = safeArea

    ctx.translate(safeArea / 2, safeArea / 2)
    ctx.rotate(rotation * Math.PI / 180)
    ctx.translate(-safeArea / 2, -safeArea / 2)

    ctx.drawImage(
        image,
        safeArea / 2 - image.naturalWidth / 2,
        safeArea / 2 - image.naturalHeight / 2
    )

    const data = ctx.getImageData(
        safeArea / 2 - image.naturalWidth / 2 + pixelCrop.x,
        safeArea / 2 - image.naturalHeight / 2 + pixelCrop.y,
        pixelCrop.width,
        pixelCrop.height
    )

    canvas.width = pixelCrop.width
    canvas.height = pixelCrop.height
    ctx.putImageData(data, 0, 0)

    let croppedBase64 = canvas.toDataURL("image/jpeg", 0.5)

    const exifBytes = piexif.dump(exifObj)
    const finalBase64 = piexif.insert(exifBytes, croppedBase64)

    return finalBase64.split(",")[1]
}