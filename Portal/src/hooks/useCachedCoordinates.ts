import { useCallback } from "react"

import { getCoordinates } from "../clients/coreClient.ts"
import type { Coordinates } from "../types/Coordinates.ts"
import type { UseCachedCoordinatesResult } from "../types/UseCachedCoordinatesResult.ts"
import { useCache } from "./useCache.ts"

// eslint-disable-next-line react-hooks/rules-of-hooks
const coordinatesCache = useCache<Record<string, Coordinates>>("useCachedCoordinates:coordinates")

export const useCachedCoordinates = (): UseCachedCoordinatesResult => {
    return useCallback(async (address: string) => {
        const map = coordinatesCache.get() ?? {}

        if (map[address]) {
            return map[address]
        }

        const coordinates = await getCoordinates(address).catch(() => null)
        if (coordinates) {
            coordinatesCache.set({ ...(coordinatesCache.get() ?? {}), [address]: coordinates }, Number.MAX_SAFE_INTEGER)
        }

        return coordinates
    }, [])
}
