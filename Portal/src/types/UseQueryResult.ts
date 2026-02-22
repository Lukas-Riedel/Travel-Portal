export interface UseQueryResult<T> {
    response?: T
    isLoading: boolean
    setResponse: <V>(response: V) => V
    refetchResponse: <V>(value: V) => V
}