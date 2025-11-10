import { getCategory, removeCategoryHighlight, updateCategoryMainHighlight, updateHighlightQualityAttributes, updateCategoryMetadata, createCategoryHighlight, updateCategoryCategory, removeCategory, updateCategoryName } from "../clients/coreClient.ts"
import type { CategoryCategory, CategoryMetadata } from "../types/CoreSwaggerTypes.ts"
import type { UseCategoryResult } from "../types/UseCategoryResult.ts"
import { ONE_DAY_SECONDS } from "../utils/timeUtils.ts"
import { useQuery } from "./useQuery.ts"

export const useCategory = (categoryId?: string): UseCategoryResult => {
    const { response, setResponse, refetchResponse } = useQuery({
        queryKey: ["getCategory", categoryId],
        queryFn: () => getCategory(categoryId),
        enabled: !!categoryId,
        staleTime: ONE_DAY_SECONDS * 1000
    })

    return {
        category: response,
        updateCategoryName: (name: string) => updateCategoryName(categoryId, name).then(setResponse),
        updateCategoryCategory: (category: CategoryCategory) => updateCategoryCategory(categoryId, category).then(setResponse),
        updateCategoryMetadata: (metadata: CategoryMetadata) => updateCategoryMetadata(categoryId, metadata).then(setResponse),
        createCategoryHighlight: (photoId: string) => createCategoryHighlight(categoryId, photoId).then(refetchResponse),
        removeCategoryHighlight: (highlightId: string) => removeCategoryHighlight(categoryId, highlightId).then(refetchResponse),
        updateCategoryMainHighlight: (highlightId: string) => updateCategoryMainHighlight(categoryId, highlightId).then(setResponse),
        updateCategoryHighlightQualityAttributes: (highlightId: string, composition?: number, sky?: number, shadows?: number, circumstances?: number, atmosphere?: number) =>
            updateHighlightQualityAttributes(highlightId, composition, sky, shadows, circumstances, atmosphere).then(refetchResponse),
        removeCategory: () => removeCategory(categoryId)
    }
}