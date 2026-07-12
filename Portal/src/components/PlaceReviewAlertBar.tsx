import { useEffect, useState } from "react"
import { PlaceReviewItem } from "../types/PlaceReviewItem"
import type { Place } from "../classes/Place"
import { TriangleAlert } from "lucide-react"
import { useTranslation } from "react-i18next"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput"
import type { Album } from "../types/CoreSwaggerTypes"

interface PlaceReviewAlertBarProps {
    place: Place | null
    onPlaceReviewed?: () => Promise<Album[]>
}

export default function PlaceReviewAlertBar({ place, onPlaceReviewed }: PlaceReviewAlertBarProps) {
    const { t } = useTranslation()
    const { showUpdatePlaceReviewedToast } = usePredefinedUserInput()

    const [checked, setChecked] = useState<Partial<Record<PlaceReviewItem, boolean>>>({})
    const progress = (Object.values(checked).filter(Boolean).length / Object.values(PlaceReviewItem).length) * 100

    useEffect(() => {
        if (progress === 100) {
            handlePlaceReviewed()
        }
    }, [progress])

    const handleChecked = (item: PlaceReviewItem) => {
        setChecked(previous => ({ ...previous, [item]: !previous[item] }))
    }

    const handlePlaceReviewed = () => {
        if (onPlaceReviewed) {
            showUpdatePlaceReviewedToast(onPlaceReviewed)
                .then(() => setChecked({}))
        }
    }

    return onPlaceReviewed && place?.dates?.map(date => date.album)?.filter(Boolean)?.some(album => !album.reviewed) && (
        <div className="bg-orange-100 border border-gray-200 rounded-2xl shadow-sm p-4 mb-4">
            <div className="flex items-center justify-between mb-3">
                <div className="flex items-center space-x-2 text-orange-600">
                    <TriangleAlert className="w-6 h-6 shrink-0 mb-[1px]" />
                    <span className="text-xl font-medium">
                        {t("place.review.alert")}
                    </span>
                </div>
                <div className="w-1/2 bg-gray-50 rounded-full h-2 overflow-hidden">
                    <div
                        className="h-2 rounded-full bg-blue-600 transition-all duration-300"
                        style={{ width: `${progress}%` }} />
                </div>
            </div>
            <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                {Object.values(PlaceReviewItem).map(item => (
                    <label
                        key={item}
                        className="flex items-center gap-2 cursor-pointer hover:bg-orange-50 p-1 rounded">
                        <input
                            type="checkbox"
                            checked={!!checked[item]}
                            onChange={() => handleChecked(item)}
                            className="accent-blue-600 w-4 h-4" />
                        <span className="text-sm text-gray-700">
                            {t(`place.review.item.${item}`)}
                        </span>
                    </label>
                ))}
            </div>
        </div>
    )
}