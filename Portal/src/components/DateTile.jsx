import { useState } from "react"
import Lightbox from "yet-another-react-lightbox"
import Fullscreen from "yet-another-react-lightbox/plugins/fullscreen"
import Counter from "yet-another-react-lightbox/plugins/counter"
import "yet-another-react-lightbox/styles.css"
import "yet-another-react-lightbox/plugins/counter.css"
import PhotoTile from "./PhotoTile"
import { TailSpin } from "react-loader-spinner"
import { getDateString } from "../utils/helpers"
import { useApi } from "../hooks/useApi"
import { useAuth } from "../contexts/AuthContext"
import { ExternalLink, Images, RefreshCcw } from "lucide-react"
import showConfirmToast from "./ConfirmToast"
import { Link } from "react-router-dom"

export default function DateTile({ place, date, onAlbumRefreshed }) {
    const api = useApi()
    const { isAdmin } = useAuth()
    const [isLoading, setIsLoading] = useState(false)
    const [galleryOpen, setGalleryOpen] = useState(false)
    const [images, setImages] = useState([])

    const openGallery = () => {
        if (images.length > 0) {
            setGalleryOpen(true)
            return
        }

        setIsLoading(true)
        api.listPlaceAlbumPhotos(place.id, date.album.id)
            .then(photos => {
                setImages(photos.map(photo => ({
                    src: photo.url + "=d"
                })))
                setGalleryOpen(true)
            })
            .catch(console.error)
            .finally(() => setIsLoading(false))
    }

    const handleAlbumRefreshed = () => {
        showConfirmToast("Opravdu chceš aktualizovat vybrané album?",
            "Album bylo úspěšně aktualizováno",
            "Nepodařilo se aktualizovat album",
            async () => onAlbumRefreshed(date.album.id)
        )
    }

    if (date.album === null) {
        return null
    }

    return (
        <div>
            <PhotoTile
                src={date.album.mainImageUrl}
                firstLineText={place.name}
                secondLineText={getDateString(date.start)}
                categories={[place.getCategory("MOST_SPECIFIC_WITH_METADATA")]}
                onClick={openGallery} />
            {isAdmin() && (
                <div className="flex justify-center gap-2 mt-2">
                    <a
                        href={date.album.permalink}
                        className="btn-large-gray">
                        <ExternalLink size={16} />
                    </a>
                    <Link
                        to={`/place/${place.id}/album/${date.album.id}`}
                        className="btn-large-gray">
                        <Images size={16} />
                    </Link>
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
                open={galleryOpen}
                close={() => setGalleryOpen(false)}
                slides={images}
                plugins={[Counter, Fullscreen]} />
        </div>
    )
}