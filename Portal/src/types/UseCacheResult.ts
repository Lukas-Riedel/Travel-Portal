export interface UseCacheResult<T> {
    get: () => T | null
    set: (value: T, ttl: number) => void
    remove: () => void
}