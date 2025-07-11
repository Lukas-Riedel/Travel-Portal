import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"

export const useCategories = ({ categories, include } = {}) => {
    const { listCategories } = useApi()
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listCategories", categories, include],
        queryFn: () => listCategories({ categories, include }),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 24,
    })

    // TODO: Map to Category objects
    return query.data
}