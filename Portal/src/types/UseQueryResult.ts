export interface UseQueryResult<T> {
    response: T | null
    isLoading: boolean
    setResponse: <V>(response: V) => V
    refetchResponse: <V>(value: V) => V
}