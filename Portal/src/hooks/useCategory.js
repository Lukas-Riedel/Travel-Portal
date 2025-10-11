import { useQuery, useQueryClient } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import { getCategory, removeCategoryHighlight, updateCategoryMainHighlight, updateHighlightQualityAttributes, updateCategoryMetadata, createCategoryHighlight, updateCategoryCategory, removeCategory } from "../clients/coreClient"

export const useCategory = categoryId => {
    const { isAdmin } = useAuth()

    const queryClient = useQueryClient()

    const query = useQuery({
        queryKey: ["getCategory", categoryId],
        queryFn: () => getCategory(categoryId),
        enabled: !!categoryId,
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 2
    })

    const setCategory = category => queryClient.setQueryData(["getCategory", categoryId], category)
    const refetchCategory = _ => query.refetch()

    return {
        // TODO: Map to Category object
        category: query.data,
        removeCategory: _ => removeCategory(categoryId),
        updateCategoryName: name => updateCategoryName(categoryId, name).then(setCategory),
        updateCategoryCategory: category => updateCategoryCategory(categoryId, category).then(setCategory),
        updateCategoryMetadata: metadata => updateCategoryMetadata(categoryId, metadata).then(setCategory),
        createCategoryHighlight: photoId => createCategoryHighlight(categoryId, photoId).then(refetchCategory),
        removeCategoryHighlight: highlightId => removeCategoryHighlight(categoryId, highlightId).then(refetchCategory),
        updateCategoryMainHighlight: highlightId => updateCategoryMainHighlight(categoryId, highlightId).then(setCategory),
        updateCategoryHighlightQualityAttributes: (highlightId, composition, sky, shadows, circumstances, atmosphere) =>
            updateHighlightQualityAttributes(highlightId, composition, sky, shadows, circumstances, atmosphere).then(refetchCategory)
    }
}