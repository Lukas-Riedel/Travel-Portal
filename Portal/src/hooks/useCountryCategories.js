import { useQuery } from "@tanstack/react-query"
import { useApi } from "./useApi"

export const useCountryCategories = () => {
    const api = useApi()

    return useQuery({
        queryKey: ["countryCategories"],
        queryFn: () => api.listCategories("COUNTRY"),
        staleTime: 1000 * 60 * 60 * 24,
    })
}