import { useState, useEffect, useMemo, useCallback } from "react"
import type { Highlightable } from "../types/Highlightable.ts"
import { AnimatePresence, motion } from "framer-motion"
import { Pause, Play, Trash2, Star, SlidersVertical, Edit2, Plus, Upload, Check } from "lucide-react"
import { TailSpin } from "react-loader-spinner"
import { getOnlyElement } from "../utils/helpers"
import { useConfiguration } from "../contexts/ConfigContext"
import { useRegularPlaces } from "../hooks/useRegularPlaces"
import { useDevices } from "../hooks/useDevices.ts"
import Cropper from "react-easy-crop"
import type { Area } from "react-easy-crop"
import Slider from "../components/Slider.tsx"
import { listPlaceAlbumPhotos } from "../clients/coreClient.ts"
import piexif from "piexifjs"
import { v4 as uuidv4 } from "uuid"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { DeviceType, PlaceIncludedEntity } from "../types/CoreSwaggerTypes.ts"
import type { Highlight, Place } from "../types/CoreSwaggerTypes.ts"
import { useOnlineAgents } from "../hooks/useOnlineAgents.ts"
import { useTranslation } from "react-i18next"

const DEFAULT_ROTATION = 0
const DEFAULT_ZOOM = 1
const DEFAULT_X_POSITION = 0
const DEFAULT_Y_POSITION = 0

const BASE_URL_DOWNLOAD_SUFFIX = "=d"
const JPG_FILE_SUFFIX = ".jpg"
const CHUNK_SIZE = 0x8000

const SLIDESHOW_INTERVAL_MS = 7000

interface HighlightCarouselProps {
    place: Place | null
    highlights: Highlight[] | null
    onPhotoReplaced?: (agentId: string, placeId: string, albumId: string, placeName: string, photoId: string, path: string, sendNotification: boolean) => Promise<void>
    onPhotoCorrected?: (placeId: string, albumId: string, filename: string, base64Data: string, photoId: string) => Promise<Highlight>
    onHighlightRemoved?: (highlightId: string) => Promise<void>
    onMainHighlightUpdated?: (highlightId: string) => Promise<Highlightable>
    onHighlightQualityAttributesUpdated?: (highlightId: string, composition: number | null, sky: number | null, shadows: number | null, circumstances: number | null, atmosphere: number | null, impression: number | null) => Promise<Highlight>
    onHighlightCreated?: (photoId: string) => Promise<Highlight>
}

export default function HighlightCarousel({ place, highlights, onPhotoReplaced, onPhotoCorrected, onHighlightRemoved, onMainHighlightUpdated, onHighlightQualityAttributesUpdated, onHighlightCreated }: HighlightCarouselProps) {
    const { t } = useTranslation()

    const onlineAgents = useOnlineAgents()
    const { showCreateHighlightToast, showUpdateHighlightToast, showRemoveHighlightToast, showUpdateMainHighlightToast, showReplacePhotoToast, showUpdateHighlightAttributesToast } = usePredefinedUserInput()

    const slideshowAutostartEnabled = !(onPhotoReplaced || onPhotoCorrected || onHighlightRemoved || onMainHighlightUpdated || onHighlightQualityAttributesUpdated)

    const [shuffledHighlights, setShuffledHighlights] = useState<Highlight[]>([])
    const [currentHighlightIndex, setCurrentHighlightIndex] = useState(0)
    const [isPaused, setIsPaused] = useState(!slideshowAutostartEnabled)
    const [showEditor, setShowEditor] = useState(false)
    const [crop, setCrop] = useState({ x: DEFAULT_X_POSITION, y: DEFAULT_Y_POSITION })
    const [zoom, setZoom] = useState(DEFAULT_ZOOM)
    const [rotation, setRotation] = useState(DEFAULT_ROTATION)
    const [croppedAreaPixels, setCroppedAreaPixels] = useState<Area | null>(null)

    const { places: currentHighlightPlaces } = useRegularPlaces({ enabled: !!shuffledHighlights[currentHighlightIndex]?.photo?.id, photoId: shuffledHighlights[currentHighlightIndex]?.photo?.id, include: [PlaceIncludedEntity.Dates] })
    const currentHighlightAlbumId = useMemo(() => getOnlyElement(currentHighlightPlaces?.flatMap(place => place.dates)?.map(date => date.album).filter(Boolean).map(album => album.id)), [currentHighlightPlaces])
    const [currentHighlightReferencePhotoUrl, setCurrentHighlightReferencePhotoUrl] = useState<string | null>(null)

    useEffect(() => {
        const fetchAndSetPhotoUrl = async (photoId: string) => {
            if (place?.id && currentHighlightAlbumId) {
                const photos = await listPlaceAlbumPhotos(place.id, currentHighlightAlbumId)
                // TODO: Introduce a new endpoint for obtaining a photo based on its identifier
                const photo = photos.find(photo => photo.id === photoId)
                setCurrentHighlightReferencePhotoUrl(photo?.url + BASE_URL_DOWNLOAD_SUFFIX)
            }
        }

        setCurrentHighlightReferencePhotoUrl(null)
        fetchAndSetPhotoUrl(shuffledHighlights[currentHighlightIndex]?.photo?.id)
    }, [place?.id, currentHighlightAlbumId, shuffledHighlights, currentHighlightIndex])

    const onCropComplete = useCallback((_: Area, croppedAreaPixels: Area) => {
        setCroppedAreaPixels(croppedAreaPixels)
    }, [])

    useEffect(() => {
        if (slideshowAutostartEnabled) {
            setShuffledHighlights([...(highlights ?? [])].sort(() => Math.random() - 0.5))
        }
        else {
            setShuffledHighlights(highlights ?? [])
        }

        setCurrentHighlightIndex(previous => Math.min((highlights ?? []).length - 1, previous))
    }, [highlights])

    useEffect(() => {
        if (isPaused) {
            return
        }

        const interval = setInterval(() => setCurrentHighlightIndex(previous => (previous + 1) % shuffledHighlights.length), SLIDESHOW_INTERVAL_MS)
        return () => clearInterval(interval)
    }, [shuffledHighlights, isPaused])

    const handleHighlightCreated = () => {
        const highlight = shuffledHighlights[currentHighlightIndex]

        showCreateHighlightToast(() => onHighlightCreated(highlight.photo.id)
            .then(result => {
                const newHighlights = [...shuffledHighlights]
                newHighlights.splice(currentHighlightIndex, 1)
                setShuffledHighlights(newHighlights)
                setCurrentHighlightIndex(previous => Math.max(0, Math.min(previous, newHighlights.length - 1)))
                return result
            }))
    }

    const handlePhotoReplaced = () => {
        showReplacePhotoToast(onlineAgents, (path, agentId, sendNotification) => onPhotoReplaced(agentId, place.id, currentHighlightAlbumId, place.name, shuffledHighlights[currentHighlightIndex].photo.id, path, sendNotification)
            .then(() => {
                window.open(shuffledHighlights[currentHighlightIndex].photo.permalink, "_blank")
            }))
    }

    const handlePhotoCorrected = () => {
        const highlight = shuffledHighlights[currentHighlightIndex]

        showUpdateHighlightToast(() => getCroppedImg(currentHighlightReferencePhotoUrl, croppedAreaPixels, rotation)
            .then(base64Data => onPhotoCorrected(place.id, currentHighlightAlbumId, uuidv4() + JPG_FILE_SUFFIX, base64Data, highlight.photo.id))
            .then(result => {
                window.open(highlight.photo.permalink, "_blank")
                return result
            }))
            .finally(() => {
                setCrop({ x: DEFAULT_X_POSITION, y: DEFAULT_Y_POSITION })
                setZoom(DEFAULT_ZOOM)
                setRotation(DEFAULT_ROTATION)
                setShowEditor(false)
            })
    }

    const handleHighlightRemoved = () => {
        const highlight = shuffledHighlights[currentHighlightIndex]

        showRemoveHighlightToast(() => onHighlightRemoved(highlight.id).then(() => {
            const newHighlights = [...shuffledHighlights]
            newHighlights.splice(currentHighlightIndex, 1)
            setShuffledHighlights(newHighlights)
            setCurrentHighlightIndex(previous => Math.max(0, Math.min(previous, newHighlights.length - 1)))
        }))
    }

    const handleMainHighlightUpdated = () => {
        const highlight = shuffledHighlights[currentHighlightIndex]

        showUpdateMainHighlightToast(() => onMainHighlightUpdated(highlight.id))
    }

    const handleHighlightQualityAttributesUpdated = () => {
        const highlight = shuffledHighlights[currentHighlightIndex]

        showUpdateHighlightAttributesToast((composition, sky, shadows, circumstances, atmosphere, impression) => onHighlightQualityAttributesUpdated(highlight.id, composition,
            sky, shadows, circumstances, atmosphere, impression), highlight?.attributes, highlight.photo.timestamp, highlight.photo.focalLength)
    }

    if (shuffledHighlights.length === 0) {
        if (!highlights) {
            return null
        }

        return (
            <div className="flex items-center justify-center w-full [aspect-ratio:3/2]">
                <TailSpin
                    color="black"
                    height={80}
                    width={80} />
            </div>
        )
    }

    return (
        <div className={`relative w-full [aspect-ratio:3/2] overflow-hidden rounded-xl shadow-lg my-4 ${showEditor && "ring-8 ring-red-600"}`}>
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
                            name={t("highlight.editor.label.rotation")}
                            value={rotation}
                            defaultValue={DEFAULT_ROTATION}
                            step={0.05}
                            minValue={-15}
                            maxValue={15}
                            onValueChanged={setRotation} />
                        <Slider
                            name={t("highlight.editor.label.zoom")}
                            value={zoom}
                            defaultValue={DEFAULT_ZOOM}
                            step={0.01}
                            minValue={1}
                            maxValue={3}
                            onValueChanged={setZoom} />
                        <Slider
                            name={t("highlight.editor.label.x")}
                            value={crop.x}
                            defaultValue={DEFAULT_X_POSITION}
                            minValue={-720}
                            maxValue={720}
                            onValueChanged={x => setCrop({ x: x, y: crop.y })} />
                        <Slider
                            name={t("highlight.editor.label.y")}
                            value={crop.y}
                            defaultValue={DEFAULT_Y_POSITION}
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
                <div className="absolute bottom-3 left-0 w-full flex justify-center">
                    <div className="flex flex-wrap justify-center gap-2 px-4">
                        {shuffledHighlights.map((_, index) => (
                            <button
                                key={index}
                                onClick={() => setCurrentHighlightIndex(index)}
                                className={`w-3 h-3 rounded-full ${index === currentHighlightIndex ? "bg-white" : "bg-white/50"}`} />
                        ))}
                    </div>
                </div>
            )}
            <div className="absolute top-3 right-3 flex space-x-2">
                {onHighlightCreated && (
                    <button
                        onClick={handleHighlightCreated}
                        className="btn-chip-gray">
                        <Plus size={16} />
                    </button>
                )}
                {onPhotoCorrected && showEditor && (rotation !== DEFAULT_ROTATION || zoom !== DEFAULT_ZOOM || crop.x !== DEFAULT_X_POSITION || crop.y !== DEFAULT_Y_POSITION) && (
                    <button
                        onClick={handlePhotoCorrected}
                        className="btn-chip-gray">
                        <Check size={16} />
                    </button>
                )}
                {onPhotoCorrected && currentHighlightReferencePhotoUrl && (
                    <button
                        onClick={() => setShowEditor(previous => !previous)}
                        className="btn-chip-gray">
                        <Edit2 size={16} />
                    </button>
                )}
                {onHighlightQualityAttributesUpdated && (
                    <button
                        onClick={handleHighlightQualityAttributesUpdated}
                        className="btn-chip-gray">
                        <SlidersVertical size={16} />
                    </button>
                )}
                {onPhotoReplaced && place && currentHighlightAlbumId && (
                    <button
                        onClick={handlePhotoReplaced}
                        className="btn-chip-gray">
                        <Upload size={16} />
                    </button>
                )}
                {shuffledHighlights.length > 1 && (
                    <>
                        <button
                            onClick={() => setIsPaused(previous => !previous)}
                            className="btn-chip-gray">
                            {isPaused ? (
                                <Play size={16} />
                            ) : (
                                <Pause size={16} />
                            )}
                        </button>
                        {onMainHighlightUpdated && (
                            <button
                                onClick={handleMainHighlightUpdated}
                                className="btn-chip-gray">
                                <Star size={16} />
                            </button>
                        )}
                        {onHighlightRemoved && (
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
    )
}

const createImage = (url: string): Promise<HTMLImageElement> =>
    new Promise((resolve, reject) => {
        const image = new Image()
        image.addEventListener("load", () => resolve(image))
        image.addEventListener("error", error => reject(error))
        image.setAttribute("crossOrigin", "anonymous")
        image.src = url
    })

const getCroppedImg = async (imageSrc: string, pixelCrop: Area, rotation: number): Promise<string> => {
    const response = await fetch(imageSrc)
    const arrayBuffer = await response.arrayBuffer()
    const bytes = new Uint8Array(arrayBuffer)

    let binary = ""
    for (let i = 0; i < bytes.length; i += CHUNK_SIZE) {
        const chunk = bytes.subarray(i, i + CHUNK_SIZE)
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
