import { Edit2, Folder } from "lucide-react"
import { useMemo } from "react"
import { useTranslation } from "react-i18next"
import { useParams } from "react-router-dom"

import { createPlaceAlbumPhoto, listPlaceAlbumPhotos, refreshPlaceAlbum } from "../clients/coreClient.ts"
import AppLink from "../components/AppLink.tsx"
import HighlightCarouselAndPlaceMapAndFlightMapToggleToggle from "../components/HighlightCarouselAndPlaceMapAndFlightMapToggleToggle.tsx"
import PageHeader from "../components/PageHeader.tsx"
import PlaceTileGrid from "../components/PlaceTileGrid.tsx"
import StatisticsPanel from "../components/StatisticsPanel.tsx"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useCategory } from "../hooks/useCategory.ts"
import { useEvents } from "../hooks/useEvents.ts"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces.ts"
import { AppLinkTarget } from "../types/AppLinkTarget.ts"
import { CategoryCategory, PlaceIncludedEntity, PlaceSortingStrategy, UserRole } from "../types/CoreSwaggerTypes.ts"
import { InternalCategoryCategory } from "../types/InternalCategoryCategory.ts"
import { getHighlightsTier } from "../utils/highlightUtils.ts"

export default function CategoryPage() {
    const { categoryId } = useParams()
    const { t } = useTranslation()
    const { publishPhotoReplacingTriggeredEvent } = useEvents()
    const { showUpdateCategoryToast } = usePredefinedUserInput()
    const { hasRole } = useAuth()

    const { category, updateCategoryName, updateCategoryCategory, updateCategoryMetadata, removeCategory, refreshCategoryHighlights,
        removeCategoryHighlight, updateCategoryMainHighlight, updateCategoryHighlightQualityAttributes } = useCategory(categoryId)
    const { places } = useTimeFilteredRegularPlaces({ categoryId, include: [PlaceIncludedEntity.Categories], sort: PlaceSortingStrategy.ValueScore })

    const countryCategoriesMap = useMemo(() => new Map(places?.map(place => place.getCategory(CategoryCategory.Country))
        ?.filter(Boolean)?.map(category => [category.name, category])), [places])

    const totalScore = useMemo(() => places?.map(place => place.score)?.filter(Boolean)
        ?.reduce((acc, score) => acc + score, 0), [places])
    const totalQuality = useMemo(() => places?.map(place => place.quality)?.filter(Boolean)
        ?.reduce((acc, quality) => acc + quality, 0), [places])
    const placesWithQualityCount = useMemo(() => places?.map(place => place.quality)?.filter(Boolean)?.length, [places])

    const attributes = {
        [t("category.attribute.category")]: category?.category && t(`category.category.${category?.category}`),
        [t("category.attribute.averageQuality")]: totalQuality && `${Math.round(totalQuality / placesWithQualityCount)}%`,
        [t("category.attribute.tier")]: category && getHighlightsTier(category?.highlights ?? [], category?.mainHighlight),
        [t("category.attribute.totalScore")]: totalScore,
        [t("category.attribute.highlightsCount")]: category?.highlights?.length
    }

    const handlePhotoCorrected = async (placeId: string, albumId: string, fileName: string, base64Data: string, photoId: string) => createPlaceAlbumPhoto(placeId, albumId, fileName, base64Data, photoId)
        .then(({ batchId }) => refreshPlaceAlbum(placeId, albumId, { batchId }))
        .then(_ => listPlaceAlbumPhotos(placeId, albumId))
        .then(photos => photos.find(photo => photo.id === photoId))

    const getPlaceCategory = place => {
        if (countryCategoriesMap.size > 1) {
            return countryCategoriesMap.get(place?.country)
        }
        if (place?.country === category?.name) {
            return category
        }
        return place?.getCategory(InternalCategoryCategory.MostSpecificWithMetadata)
    }

    const handleMetadataChanged = () => {
        showUpdateCategoryToast(category, updateCategoryMetadata, updateCategoryCategory)
    }

    return hasRole(UserRole.CategoryRead) && (
        <>
            <PageHeader
                name={category?.name ?? null}
                categories={category?.metadata ? [category] : [...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))}
                internalAttributes={hasRole(UserRole.CategoryEdit) && attributes}
                onHighlightsRefreshed={hasRole(UserRole.CategoryHighlightEdit) && totalScore > 0 && (highlightsCount => refreshCategoryHighlights(highlightsCount))}
                onNameChanged={hasRole(UserRole.CategoryEdit) && updateCategoryName}
                onRemoved={hasRole(UserRole.CategoryEdit) && category?.category !== CategoryCategory.Country && removeCategory} />
            <HighlightCarouselAndPlaceMapAndFlightMapToggleToggle
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
                    {category && (
                        <AppLink
                            target={AppLinkTarget.Plans}
                            to={category}
                            className="btn-chip-gray">
                            <Folder size={16} />
                        </AppLink>
                    )}
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
