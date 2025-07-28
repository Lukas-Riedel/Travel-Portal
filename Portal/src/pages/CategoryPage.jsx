import { useParams } from "react-router-dom"
import { useCategory } from "../hooks/useCategory"
import PageHeader from "../components/PageHeader"
import HighlightCarouselAndPlaceMapToggle from "../components/HighlightCarouselAndPlaceMapToggle"
import PlaceTileGrid from "../components/PlaceTileGrid"
import StatisticsPanel from "../components/StatisticsPanel"
import { useMemo } from "react"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"
import { useEvents } from "../hooks/useEvents"
import { useAuth } from "../contexts/AuthContext"
import { Edit2 } from "lucide-react"
import showFormToast from "../components/FormToast"

export default function CategoryPage() {
    const { categoryId } = useParams()
    const { publishPhotoReplacingTriggeredEvent } = useEvents()

    const { isAdmin } = useAuth()

    const { category, updateCategoryName, updateCategoryMetadata, removeCategoryHighlight, updateCategoryMainHighlight, updateCategoryHighlightQualityAttributes } = useCategory(categoryId)
    const categoryPlaces = useTimeFilteredRegularPlaces({ categoryId, include: "CATEGORIES", sort: "score" })

    const countryCategoriesMap = useMemo(() => new Map(categoryPlaces?.map(place => place.getCategory("COUNTRY"))
        ?.filter(Boolean)?.map(category => [category.name, category])), [categoryPlaces])

    const totalScore = useMemo(() => categoryPlaces?.map(place => place.score).filter(Boolean)
        .reduce((acc, score) => acc + score, 0), [categoryPlaces])
    const totalQuality = useMemo(() => categoryPlaces?.map(place => place.quality).filter(Boolean)
        .reduce((acc, quality) => acc + quality, 0), [categoryPlaces])

    const getPlaceCategory = place => {
        if (countryCategoriesMap.size > 1) {
            return countryCategoriesMap.get(place?.country)
        }
        if (place?.country === category?.name) {
            return category
        }
        return place?.getCategory("MOST_SPECIFIC_WITH_METADATA")
    }

    const handleMetadataChanged = () => {
        showFormToast(
            "Zadej metadata kategorie:",
            [
                { label: "Barva", required: false, value: category.metadata?.color },
                { label: "Unicode", required: false, value: category.metadata?.unicode },
                { label: "Kalendář", required: false, value: category.metadata?.publicHolidaysCalendar }
            ],
            "Metadata byla úspěšně aktualizována",
            "Při aktualizování metadat došlo k chybě",
            async (color, unicode, publicHolidaysCalendar) => updateCategoryMetadata({ color, unicode, publicHolidaysCalendar })
        )
    }

    return (
        <>
            <PageHeader
                name={category?.name}
                categories={category?.metadata ? [category] : [...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))}
                internalAttributes={{ "Průměrná kvalita": totalQuality && `${Math.round(totalQuality / categoryPlaces.length)}%`, "Celkové skóre": totalScore }}
                onNameChanged={updateCategoryName} />
            <HighlightCarouselAndPlaceMapToggle
                entity={category}
                places={categoryPlaces}
                placeMainCategorySelector={getPlaceCategory}
                onPhotoReplaced={publishPhotoReplacingTriggeredEvent}
                onHighlightRemoved={removeCategoryHighlight}
                onMainHighlightUpdated={updateCategoryMainHighlight}
                onHighlightQualityAttributesUpdated={updateCategoryHighlightQualityAttributes} />
            <StatisticsPanel statistics={category?.statistics} />
            <PlaceTileGrid
                places={categoryPlaces}
                placeMainCategorySelector={getPlaceCategory} />
            {isAdmin && (
                <div className="flex justify-end">
                    <div className="flex items-center gap-2">
                        <button
                            onClick={handleMetadataChanged}
                            className="btn-chip-gray">
                            <Edit2 size={16} />
                        </button>
                    </div>
                </div>
            )}
        </>
    )
}