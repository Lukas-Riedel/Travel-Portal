import { Edit2, Folder } from "lucide-react"
import { useTranslation } from "react-i18next"
import { useParams } from "react-router-dom"

import { createPlaceAlbumPhoto, listPlaceAlbumPhotos, refreshPlaceAlbum } from "../clients/coreClient.ts"
import AppLink from "../components/AppLink.tsx"
import HighlightCarouselAndPlaceMapAndFlightMapToggleToggle from "../components/HighlightCarouselAndPlaceMapAndFlightMapToggleToggle.tsx"
import PageHeader from "../components/PageHeader"
import PlaceTileGrid from "../components/PlaceTileGrid"
import { useAuth } from "../contexts/AuthContext.tsx"
import { useEvents } from "../hooks/useEvents.ts"
import { useLabel } from "../hooks/useLabel"
import { usePredefinedUserInput } from "../hooks/usePredefinedUserInput.ts"
import { useTimeFilteredRegularPlaces } from "../hooks/useTimeFilteredRegularPlaces"
import { AppLinkTarget } from "../types/AppLinkTarget.ts"
import { CategoryCategory, PlaceIncludedEntity, PlaceSortingStrategy, UserRole } from "../types/CoreSwaggerTypes.ts"
import { getPlaceCategory } from "../utils/placeUtils.ts"

export default function LabelPage() {
    const { labelId } = useParams()
    const { t } = useTranslation()
    const { hasRole } = useAuth()
    const { publishPhotoReplacingTriggeredEvent } = useEvents()
    const { showUpdateLabelToast } = usePredefinedUserInput()

    const { label, updateLabelName, updateLabelMetadata, refreshLabelHighlights,
        removeLabelHighlight, updateLabelMainHighlight, updateLabelHighlightQualityAttributes } = useLabel(labelId)
    const { places } = useTimeFilteredRegularPlaces({ labelId, include: [PlaceIncludedEntity.Categories], sort: PlaceSortingStrategy.ValueScore })

    const countryCategoriesMap = new Map(places?.map(place => getPlaceCategory(place, CategoryCategory.Country))
        ?.filter((c): c is NonNullable<typeof c> => c != null)?.map(category => [category.name, category]))

    const totalScore = places?.map(place => place.score)?.filter((s): s is NonNullable<typeof s> => s != null)
        ?.reduce((acc, score) => acc + score, 0)

    const attributes: Record<string, string | number | undefined> = {
        [t("label.attribute.highlightsCount")]: label?.highlights?.length
    }

    const handlePhotoCorrected = async (placeId: string, albumId: string, fileName: string, base64Data: string, photoId: string) => createPlaceAlbumPhoto(placeId, albumId, fileName, base64Data, photoId)
        .then(({ batchId }) => refreshPlaceAlbum(placeId, albumId, { batchId }))
        .then(_ => listPlaceAlbumPhotos(placeId, albumId))
        .then(photos => photos.find(photo => photo.id === photoId)!)

    const handleMetadataChanged = () => {
        if (label) {
            showUpdateLabelToast(label, updateLabelMetadata)
        }
    }

    return hasRole(UserRole.LabelRead) && (
        <>
            <PageHeader
                name={label?.name ?? null}
                onNameChanged={hasRole(UserRole.LabelEdit) ? updateLabelName : undefined}
                categories={[...countryCategoriesMap.values()].sort((a, b) => a.name.localeCompare(b.name))}
                internalAttributes={hasRole(UserRole.LabelHighlightRead) ? attributes : undefined}
                onHighlightsRefreshed={hasRole(UserRole.LabelHighlightEdit) && (totalScore ?? 0) > 0 ? (highlightsCount => refreshLabelHighlights(highlightsCount)) : undefined} />
            <HighlightCarouselAndPlaceMapAndFlightMapToggleToggle
                entity={label}
                places={places}
                placeMainCategorySelector={place => countryCategoriesMap.get(place.country ?? "") ?? null}
                onPhotoReplaced={hasRole(UserRole.PlaceAlbumEdit) ? publishPhotoReplacingTriggeredEvent : undefined}
                onPhotoCorrected={hasRole(UserRole.PlaceAlbumEdit) ? handlePhotoCorrected : undefined}
                onHighlightRemoved={hasRole(UserRole.LabelHighlightEdit) ? removeLabelHighlight : undefined}
                onMainHighlightUpdated={hasRole(UserRole.LabelEdit) ? updateLabelMainHighlight : undefined}
                onHighlightQualityAttributesUpdated={hasRole(UserRole.HighlightEdit) ? updateLabelHighlightQualityAttributes : undefined} />
            <PlaceTileGrid
                places={places}
                placeMainCategorySelector={place => countryCategoriesMap.get(place.country ?? "") ?? null} />
            <div className="flex justify-end">
                <div className="flex items-center gap-2">
                    {label && (
                        <AppLink
                            target={AppLinkTarget.Plans}
                            to={label}
                            className="btn-chip">
                            <Folder size={16} />
                        </AppLink>
                    )}
                    {hasRole(UserRole.LabelEdit) && (
                        <button
                            onClick={handleMetadataChanged}
                            className="btn-chip">
                            <Edit2 size={16} />
                        </button>
                    )}
                </div>
            </div>
        </>
    )
}
