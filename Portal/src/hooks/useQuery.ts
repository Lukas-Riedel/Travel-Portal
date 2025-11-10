import { useQueryClient, useQuery as doUseQuery } from "@tanstack/react-query"
import type { UndefinedInitialDataOptions } from "@tanstack/react-query"
import type { UseQueryResult } from "../types/UseQueryResult.ts"
import { useAuth } from "../contexts/AuthContext.jsx"

export const useQuery = <T>(options: UndefinedInitialDataOptions<T, Error, T, string[]>): UseQueryResult<T> => {
    const { isAdmin } = useAuth()
    const queryClient = useQueryClient()

    const query = doUseQuery({
        ...options,
        staleTime: isAdmin ? 0 : options.staleTime
    }, queryClient)

    return {
        response: query.data,
        isLoading: query.isLoading,
        setResponse: (response: T) => queryClient.setQueryData(options.queryKey, response),
        refetchResponse: () => query.refetch()
    }
}