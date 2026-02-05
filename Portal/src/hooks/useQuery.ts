import { useQueryClient, useQuery as doUseQuery } from "@tanstack/react-query"
import type { UndefinedInitialDataOptions } from "@tanstack/react-query"
import type { UseQueryResult } from "../types/UseQueryResult.ts"
import { useAuth } from "../contexts/AuthContext.jsx"
import { UserRole } from "../types/CoreSwaggerTypes.ts"

export const useQuery = <T>(options: UndefinedInitialDataOptions<T, Error, T, string[]>): UseQueryResult<T> => {
    const { hasRole } = useAuth()
    const queryClient = useQueryClient()

    const query = doUseQuery({
        ...options,
        // Find a better role if this proves to be insufficient.
        // The idea is not to cache if the user can view future events, because they tend to change frequently.
        staleTime: hasRole(UserRole.UiFutureRead) ? 0 : options.staleTime
    }, queryClient)

    return {
        response: query.data,
        isLoading: query.isLoading,
        setResponse: <V>(response: V) => {
            queryClient.setQueryData(options.queryKey, response)
            return response
        },
        refetchResponse: <V>(response: V) => {
            query.refetch()
            return response
        }
    }
}