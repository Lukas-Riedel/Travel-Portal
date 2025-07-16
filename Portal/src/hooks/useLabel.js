import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"
import { useAuth } from "../contexts/AuthContext"

export const useLabel = (labelId) => {
    const { getLabel } = useApi()
    const { isAdmin } = useAuth()

    const query = useQuery({
        queryKey: ["getLabel", labelId],
        queryFn: () => getLabel(labelId),
        staleTime: isAdmin ? 0 : 1000 * 60 * 60 * 12,
    })

    // TODO: Map to Label object
    return query.data
}