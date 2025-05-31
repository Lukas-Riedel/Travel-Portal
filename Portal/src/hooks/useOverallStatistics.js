import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"

export const useOverallStatistics = () => {
    const api = useApi()

    return useQuery({
        queryKey: ["overallStatistics"],
        queryFn: () => api.listStatistics(),
        staleTime: 1000 * 60 * 60 * 6,
    })
}