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

const categoryCategories = {
    continent: "Kontinent",
    country: "Stát",
    administrative: "Administrativní oblast",
    ocean: "Oceán",
    sea: "Moře",
    bay: "Záliv",
    island: "Ostrov",
    region: "Geografický region"
}

export default function CategoryPage() {
    const { categoryId } = useParams()
    const { publishPhotoReplacingTriggeredEvent } = useEvents()

    const { isAdmin } = useAuth()

    const { category, updateCategoryName, updateCategoryCategory, updateCategoryMetadata, removeCategory,
        removeCategoryHighlight, updateCategoryMainHighlight, updateCategoryHighlightQualityAttributes } = useCategory(categoryId)
    const { places } = useTimeFilteredRegularPlaces({ categoryId, include: "categories", sort: "-score" })

    const countryCategoriesMap = useMemo(() => new Map(places?.map(place => place.getCategory("country"))
        ?.filter(Boolean)?.map(category => [category.name, category])), [places])

    const totalScore = useMemo(() => places?.map(place => place.score).filter(Boolean)
        .reduce((acc, score) => acc + score, 0), [places])
    const totalQuality = useMemo(() => places?.map(place => place.quality).filter(Boolean)
        .reduce((acc, quality) => acc + quality, 0), [places])

    const attributes = {
        "Kategorie": categoryCategories[category?.category] ?? category?.category,
        "Průměrná kvalita": totalQuality && `${Math.round(totalQuality / places.length)}%`,
        "Celkové skóre": totalScore
    }

    const getPlaceCategory = place => {
        if (countryCategoriesMap.size > 1) {
            return countryCategoriesMap.get(place?.country)
        }
        if (place?.country === category?.name) {
            return category
        }
        return place?.getCategory("mostSpecificWithMetadata")
    }

    const handleMetadataChanged = () => {
        showFormToast(
            "Zadej metadata kategorie:",
            [
                { label: "Kategorie", required: true, value: category.category, type: "select", options: Object.keys(categoryCategories).map(categoryCategory => ({ id: categoryCategory, name: categoryCategories[categoryCategory] })) },
                { label: "Barva", required: false, value: category.metadata?.color },
                { label: "Unicode", required: false, value: category.metadata?.unicode },
                { label: "Kalendář", required: false, value: category.metadata?.publicHolidaysCalendar }
            ],
            "Kategorie byla úspěšně aktualizována",
            "Při aktualizování kategorie došlo k chybě",
            async (category, color, unicode, publicHolidaysCalendar) =>
                updateCategoryCategory(category).then(() => updateCategoryMetadata({ color, unicode, publicHolidaysCalendar }))
        )
    }

    return (
        <>
            <PageHeader
                name={category?.name}
                categories={category?.metadata ? [category] : [...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))}
                internalAttributes={attributes}
                showHighlightsButton={totalScore > 0}
                onNameChanged={updateCategoryName}
                onRemoved={category?.category !== "country" && removeCategory} />
            <HighlightCarouselAndPlaceMapToggle
                entity={category}
                places={places}
                placeMainCategorySelector={getPlaceCategory}
                onPhotoReplaced={publishPhotoReplacingTriggeredEvent}
                onHighlightRemoved={removeCategoryHighlight}
                onMainHighlightUpdated={updateCategoryMainHighlight}
                onHighlightQualityAttributesUpdated={updateCategoryHighlightQualityAttributes} />
            <StatisticsPanel statistics={category && (category.statistics ?? [])} />
            <PlaceTileGrid
                places={places}
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