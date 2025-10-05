import { useQuery } from "@tanstack/react-query"
import { useAuth } from "../contexts/AuthContext"
import { listCategories } from "../clients/coreClient"

export const useCategories = ({ categories, include } = {}) => {
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["listCategories", categories, include],
        queryFn: () => listCategories({ categories, include }),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 24
    })

    // TODO: Map to Category objects
    return query.data
}