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
import { Edit2, Folder } from "lucide-react"
import { createPlaceAlbumPhoto, refreshPlaceAlbum } from "../clients/coreClient"
import { useUserInput } from "../hooks/useUserInput.tsx"
import { UserRole } from "../types/CoreSwaggerTypes.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"

// TODO: This is duplicated in MainLayout. Replace by t(`category.category.${categoryCategory}`).
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
    const { showUpdateCategoryToast } = usePredefinedUserInput()

    const { hasRole } = useAuth()

    const { category, updateCategoryName, updateCategoryCategory, updateCategoryMetadata, removeCategory, refreshCategoryHighlights,
        removeCategoryHighlight, updateCategoryMainHighlight, updateCategoryHighlightQualityAttributes } = useCategory(categoryId)
    const { places } = useTimeFilteredRegularPlaces({ categoryId, include: ["categories"], sort: "-score" })

    const countryCategoriesMap = useMemo(() => new Map(places?.map(place => place.getCategory("country"))
        ?.filter(Boolean)?.map(category => [category.name, category])), [places])

    const totalScore = useMemo(() => places?.map(place => place.score)?.filter(Boolean)
        ?.reduce((acc, score) => acc + score, 0), [places])
    const totalQuality = useMemo(() => places?.map(place => place.quality)?.filter(Boolean)
        ?.reduce((acc, quality) => acc + quality, 0), [places])
    const placesWithQualityCount = useMemo(() => places?.map(place => place.quality)?.filter(Boolean)?.length, [places])    

    const attributes = {
        "Kategorie": categoryCategories[category?.category] ?? category?.category,
        "Průměrná kvalita": totalQuality && `${Math.round(totalQuality / placesWithQualityCount)}%`,
        "Celkové skóre": totalScore,
        "Počet highlightů": category?.highlights?.length
    }

    const handlePhotoCorrected = async (placeId, albumId, fileName, data, replacedPhotoId) => createPlaceAlbumPhoto(placeId, albumId, fileName, data, replacedPhotoId).then(({ batchId }) => refreshPlaceAlbum(placeId, albumId, { batchId }))

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
        showUpdateCategoryToast(category, updateCategoryMetadata, updateCategoryCategory)
    }

    return hasRole(UserRole.CategoryRead) && (
        <>
            <PageHeader
                name={category?.name}
                categories={category?.metadata ? [category] : [...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))}
                internalAttributes={hasRole(UserRole.CategoryEdit) && attributes}
                onHighlightsRefreshed={hasRole(UserRole.CategoryHighlightEdit) && totalScore > 0 && (highlightsCount => refreshCategoryHighlights(highlightsCount))}
                onNameChanged={hasRole(UserRole.CategoryEdit) && updateCategoryName}
                onRemoved={hasRole(UserRole.CategoryEdit) && category?.category !== "country" && removeCategory} />
            <HighlightCarouselAndPlaceMapToggle
                entity={category}
                places={places}
                placeMainCategorySelector={getPlaceCategory}
                onPhotoReplaced={hasRole(UserRole.PlaceAlbumEdit) && publishPhotoReplacingTriggeredEvent}
                onPhotoCorrected={hasRole(UserRole.PlaceAlbumEdit) && handlePhotoCorrected}
                onHighlightRemoved={hasRole(UserRole.CategoryHighlightEdit) && removeCategoryHighlight}
                onMainHighlightUpdated={hasRole(UserRole.CategoryEdit) && updateCategoryMainHighlight}
                onHighlightQualityAttributesUpdated={hasRole(UserRole.HighlightEdit) && updateCategoryHighlightQualityAttributes} />
            <StatisticsPanel statistics={category && (category.statistics ?? [])} />
            <PlaceTileGrid
                places={places}
                placeMainCategorySelector={getPlaceCategory} />
            <div className="flex justify-end">
                <div className="flex items-center gap-2">
                    <a
                        href={`/plan/category/${category?.id}`}
                        className="btn-chip-gray">
                        <Folder size={16} />
                    </a>
                    {hasRole(UserRole.CategoryEdit) && (
                        <button
                            onClick={handleMetadataChanged}
                            className="btn-chip-gray">
                            <Edit2 size={16} />
                        </button>
                    )}
                </div>
            </div>
        </>
    )
}