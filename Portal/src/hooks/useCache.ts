import { getCurrentTimestamp } from "../utils/timeUtils.ts"
import type { UseCacheResult } from "../types/UseCacheResult.ts"

export const useCache = <T>(key: string, storage: Storage = localStorage): UseCacheResult<T> => {
    return {
        get: () => {
            try {
                const rawItem = storage.getItem(key)
                if (!rawItem) {
                    return null
                }

                const item: StoredItem<T> = JSON.parse(rawItem)
                if (item.expiration && item.expiration < getCurrentTimestamp()) {
                    storage.removeItem(key)
                    return null
                }

                return item.value
            }
            catch {
                return null
            }
        },
        set: (value: T, ttl: number) => {
            try {
                const expiration = getCurrentTimestamp() + ttl
                const item: StoredItem<T> = { value, expiration }
                storage.setItem(key, JSON.stringify(item))
            }
            catch {
                // Do nothing.
            }
        },
        remove: () => {
            storage.removeItem(key)
        }
    }
}

interface StoredItem<T> {
    value: T
    expiration?: number
}