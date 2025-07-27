import { useQuery, useQueryClient } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"

export const useCategory = categoryId => {
    const { getCategory, removeCategoryHighlight, updateCategoryMainHighlight, updateHighlightQualityAttributes } = useApi()
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
        updateCategoryName: name => updateCategoryName(categoryId, name).then(setCategory),
        removeCategoryHighlight: highlightId => removeCategoryHighlight(categoryId, highlightId).then(refetchCategory),
        updateCategoryMainHighlight: highlightId => updateCategoryMainHighlight(categoryId, highlightId).then(setCategory),
        updateCategoryHighlightQualityAttributes: (highlightId, composition, sky, shadows, circumstances, atmosphere) =>
            updateHighlightQualityAttributes(highlightId, composition, sky, shadows, circumstances, atmosphere).then(refetchCategory)
    }
}