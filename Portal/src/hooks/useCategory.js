import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"

export const useCategory = (categoryId) => {
    const api = useApi()
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["getCategory", categoryId],
        queryFn: () => api.getCategory(categoryId),
        staleTime: isAdmin() ? 0 : 1000 * 60 * 60 * 2,
    })

    const setCategory = category => queryClient.setQueryData(["getCategory", categoryId], category)
    const refetchCategory = _ => query.refetch()

    return {
        // TODO: Map to Category object
        category: query.data,
        updateCategoryName: name => api.updateCategoryName(categoryId, name).then(setCategory),
        removeCategoryHighlight: highlightId => api.removeCategoryHighlight(categoryId, highlightId).then(refetchCategory),
        updateCategoryMainHighlight: highlightId => api.updateCategoryMainHighlight(categoryId, highlightId).then(setCategory),
    }
}