export interface UseQueryResult<T> {
    response?: T
    isLoading: boolean
    setResponse: (response: T) => void
    refetchResponse: () => void
}