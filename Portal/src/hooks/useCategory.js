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

    // TODO: Map to Category object
    return query.data
}