import { useEffect, useState } from "react"
import Lightbox, { type SlideImage } from "yet-another-react-lightbox"
import Fullscreen from "yet-another-react-lightbox/plugins/fullscreen"
import Counter from "yet-another-react-lightbox/plugins/counter"
import "yet-another-react-lightbox/styles.css"
import "yet-another-react-lightbox/plugins/counter.css"
import PhotoTile from "./PhotoTile.tsx"
import { TailSpin } from "react-loader-spinner"
import { ExternalLink, Images, RefreshCcw } from "lucide-react"
import { Link } from "react-router-dom"
import { usePlaceAlbumPhotos } from "../hooks/usePlaceAlbumPhotos.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import type { Place } from "../classes/Place.ts"
import type { Album, Date, Photo } from "../types/CoreSwaggerTypes.ts"
import { useTranslation } from "react-i18next"
import { formatTimestamp } from "../utils/timeUtils.ts"
import { InternalCategoryCategory } from "../types/InternalCategoryCategory.ts"
import AppLink from "./AppLink.tsx"

interface DateTileProps {
    place: Place | null
    date: Date | null
    onAlbumRefreshed?: (albumId: string) => Promise<Album>
}

export default function DateTile({ place, date, onAlbumRefreshed }: DateTileProps) {
    const { t } = useTranslation()
    const { showRefreshAlbumToast } = usePredefinedUserInput()
    const photos = usePlaceAlbumPhotos(place?.id, date?.album?.id)

    const [isLoading, setIsLoading] = useState(false)
    const [isGalleryOpen, setIsGalleryOpen] = useState(false)
    const [images, setImages] = useState<SlideImage[]>([])

    useEffect(() => {
        if (isLoading && photos?.length > 0) {
            setImages(photos.map(photo => ({ src: photo.url + "=d" })))
            setIsLoading(false)
            setIsGalleryOpen(true)
        }
    }, [photos, isLoading])

    const openGallery = () => {
        if (photos?.length > 0) {
            setImages(photos.map(photo => ({ src: photo.url + "=d" })))
            setIsGalleryOpen(true)
        }
        else {
            setIsLoading(true)
        }
    }

    const handleAlbumRefreshed = () => {
        if (date?.album && onAlbumRefreshed) {
            showRefreshAlbumToast(() => onAlbumRefreshed(date.album.id))
        }
    }

    return (
        <div>
            <PhotoTile
                src={date?.album?.mainImageUrl}
                firstLineText={place?.name}
                secondLineText={date && formatTimestamp(date.start, t("general.format.date.year.included"), place?.timezone)}
                categories={place && [place.getCategory(InternalCategoryCategory.MostSpecificWithMetadata)]}
                onClick={openGallery} />
            {onAlbumRefreshed && date?.album && (
                <div className="flex justify-center gap-2 mt-2">
                    <a
                        href={date.album.permalink}
                        className="btn-large-gray"
                        target="_blank"
                        rel="noopener noreferrer">
                        <ExternalLink size={16} />
                    </a>
                    <AppLink
                        to={{ place, album: date.album }}
                        className="btn-large-gray">
                        <Images size={16} />
                    </AppLink>
                    {onAlbumRefreshed && (
                        <button
                            onClick={handleAlbumRefreshed}
                            className="btn-large-gray">
                            <RefreshCcw size={16} />
                        </button>
                    )}
                </div>
            )}
            {isLoading && (
                <div style={{
                    position: "fixed",
                    top: 0,
                    left: 0,
                    width: "100vw",
                    height: "100vh",
                    backgroundColor: "rgba(0, 0, 0, 0.6)",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    zIndex: 9999
                }}>
                    <TailSpin
                        color="#ffffff"
                        height={80}
                        width={80} />
                </div>
            )}
            <Lightbox
                open={isGalleryOpen}
                close={() => setIsGalleryOpen(false)}
                slides={images}
                plugins={[Counter, Fullscreen]} />
        </div>
    )
}