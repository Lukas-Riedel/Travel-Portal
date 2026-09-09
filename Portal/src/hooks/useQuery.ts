import type { UndefinedInitialDataOptions } from "@tanstack/react-query"
import { useQuery as doUseQuery,useQueryClient } from "@tanstack/react-query"

import { useAuth } from "../contexts/AuthContext.jsx"
import { UserRole } from "../types/CoreSwaggerTypes.ts"
import type { UseQueryResult } from "../types/UseQueryResult.ts"

export const useQuery = <T>(options: UndefinedInitialDataOptions<T, Error, T, (string | undefined)[]>): UseQueryResult<T> => {
    const { hasRole } = useAuth()
    const queryClient = useQueryClient()

    const query = doUseQuery({
        ...options,
        // Find a better role if this proves to be insufficient.
        // The idea is not to cache if the user can view future events, because they tend to change frequently.
        staleTime: hasRole(UserRole.PortalFutureRead) ? 0 : options.staleTime,
        enabled: options.enabled === undefined || !!options.enabled,
    }, queryClient)

    return {
        response: query.data ?? null,
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